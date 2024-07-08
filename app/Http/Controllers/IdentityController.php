<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdentityController extends Controller
{
    public function identify(Request $request)
    {
        $email = $request->input('email');
        $phone_number = $request->input('phoneNumber');

        error_log('Received email: ' . $email . ', phone number: ' . $phone_number);

        // Query to check if a contact exists with the given phone number or email
        $exisiting_contact_count = DB::table('contacts')
                    ->where('phoneNumber', $phone_number)
                    ->orWhere('email',$email)
                    ->count();

        if($exisiting_contact_count==0){
            // Create a new contact
            $new_contact_id = $this->insert_record(NULL,$email,$phone_number,'primary');

            $new_contact = DB::table('contacts')->where('id', $new_contact_id)->first();

            // return the single new record
            return response()->json($new_contact);
        }
        else{
            // Get the primary email matching record for the given request email
            $matching_contact_by_email = DB::table('contacts')
            ->where('email', $email)
            ->where('linkPrecedence', 'primary')
            ->first();

            // Get the primary phone matching record for the given request phone
            $matching_contact_by_phone = DB::table('contacts')
            ->where('phoneNumber', $phone_number)
            ->where('linkPrecedence','primary')
            ->first();

            if(!$matching_contact_by_email && $matching_contact_by_phone){
                // Case when we get a match to a primary phone record

                $this->insert_record($matching_contact_by_phone->id,$email,$phone_number,'secondary');
                
                return $this->coalesced_results();
            }
            else if(!$matching_contact_by_phone && $matching_contact_by_email){
                // Case when we get a match to a primary email record

                $this->insert_record($matching_contact_by_email->id,$email,$phone_number,'secondary');

                return $this->coalesced_results();
            }
            else if($matching_contact_by_email && $matching_contact_by_phone && $matching_contact_by_email->id == $matching_contact_by_phone->id){
                // Case when we get a match to both primary email and primary phone and id are also same

                // nothing to modify in database

                return $this->duplicate_order();
            }
            else if($matching_contact_by_email && $matching_contact_by_phone && $matching_contact_by_email->id != $matching_contact_by_phone->id){
                // Case when we get a match to both primary email and primary phone and id are different

                $this->update_record($matching_contact_by_email,$matching_contact_by_phone);

                return $this->coalesced_results();
            }
            else{
                // Case when we get a match with a secondary record with either id or phone

                return $this->coalesced_results();
            }

        }

    }

    public function duplicate_order(){
        return response()->json(['error' => 'Orders were not placed due to duplicate identity'], 400);
    }

    public function coalesced_results(){

    }

    public function insert_record($linked_id,$email,$phone_number,$link_precedence){
        $new_contact_id = DB::table('contacts')->insertGetId([
            'phoneNumber' => $phone_number,
            'email' => $email,
            'linkPrecedence' => $link_precedence,
            'linkedId' => $linked_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $new_contact_id;
    }

    public function update_record($email_record,$phone_record){

    }
}
