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
            return $this->coalesced_results($new_contact,true);
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
                
                return $this->coalesced_results($matching_contact_by_phone,true);
            }
            else if(!$matching_contact_by_phone && $matching_contact_by_email){
                // Case when we get a match to a primary email record

                $this->insert_record($matching_contact_by_email->id,$email,$phone_number,'secondary');

                return $this->coalesced_results($matching_contact_by_email,true);
            }
            else if($matching_contact_by_email && $matching_contact_by_phone && $matching_contact_by_email->id == $matching_contact_by_phone->id){
                // Case when we get a match to both primary email and primary phone and id are also same

                // nothing to modify in database

                return $this->duplicate_order();
            }
            else if($matching_contact_by_email && $matching_contact_by_phone && $matching_contact_by_email->id != $matching_contact_by_phone->id){
                // Case when we get a match to both primary email and primary phone and id are different

                $this->update_record($matching_contact_by_email,$matching_contact_by_phone);

                return $this->coalesced_results($matching_contact_by_email,true);
            }
            else{
                // Case when we get a match with a secondary record with either id or phone

                // Get the secondary email matching record for the given request email
                $matching_contact_by_email_secondary = DB::table('contacts')
                ->where('email', $email)
                ->first();

                // Get the secondary phone matching record for the given request phone
                $matching_contact_by_phone_secondary = DB::table('contacts')
                ->where('phoneNumber', $phone_number)
                ->first();

                if($matching_contact_by_email_secondary && !$matching_contact_by_phone_secondary){
                    // Case when we get a match with a secondary email

                    $this->insert_record($matching_contact_by_email_secondary->linkedId,$email,$phone_number,'secondary');

                    return $this->coalesced_results($matching_contact_by_email_secondary,true);
                }
                else if(!$matching_contact_by_email_secondary && $matching_contact_by_phone_secondary){
                    // Case when we get a match with a secondary phone

                    $this->insert_record($matching_contact_by_phone_secondary->linkedId,$email,$phone_number,'secondary');

                    return $this->coalesced_results($matching_contact_by_phone_secondary,true);
                }
                else{
                    // Case when both match the same secondary record

                    // nothing to modify in database

                    return $this->duplicate_order();
                }
            }

        }

    }

    public function duplicate_order(){
        return response()->json(['error' => 'Orders were not placed due to duplicate identity'], 400);
    }

    public function coalesced_results($record,$is_primary){

        // since we are passing secondary record for match with a secondary record hence we get the primary record corresponding to the secondary record
        if(!$is_primary){
            $record = DB::table('contacts')->where('id',$record->linkedId)->first();
        }

        $linked_contacts = DB::table('contacts')->where('linkedId', $record->id)->get();
    
        $emails = [$record->email];
        $phoneNumbers = [$record->phoneNumber];
        $secondaryContactIds = [];
    
        foreach ($linked_contacts as $contact) {
            $emails[] = $contact->email;
            $phoneNumbers[] = $contact->phoneNumber;
            $secondaryContactIds[] = $contact->id;
        }
    
        // Remove duplicate values
        $emails = array_unique($emails);
        $phoneNumbers = array_unique($phoneNumbers);
        $secondaryContactIds = array_unique($secondaryContactIds);
    
        return response()->json([
            'contact' => [
                'primaryContactId' => $record->id,
                'emails' => array_values($emails), // array_values to reindex array after removing duplicates
                'phoneNumbers' => array_values($phoneNumbers),
                'secondaryContactIds' => array_values($secondaryContactIds),
            ]
        ]);
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
        DB::table('contacts')
        ->where('id', $phone_record->id)
        ->update([
            'linkPrecedence' => 'secondary',
            'linkedId' => $email_record->id,
            'updated_at' => now(),
        ]);
    }
}
