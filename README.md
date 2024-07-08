# Shopping App

### Made with Laravel PHP

- Routes are defined under [api.php](./routes/api.php)

- Business logic is defined under [IdentityController.php](./app/Http/Controllers/IdentityController.php)

### Connected to a Supabase instance (PostgreSQL database)

- DB schema can be checked via the migrations used under [contact_migration](./database/migrations/2024_07_07_192849_create_contacts_table.php)

### Hosted on Render.com

- Can accept post requests on this [URL]();

### Additional Improvements

- Adding to the problem statement it is also possible to chain secondary contacts
- Assume A is a primary contact with (x,y), B is a secondary contact with (x,z) then any new contact C with (z,k) will automatically be chained to A. 

## Workflow

- Defines the business logic pictorially

![Image](./storage/docimg.png)

