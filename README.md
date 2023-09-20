# Catalyst IT

## User uploader

- --file [csv file name] – this is the name of the CSV to be parsed
- --dry_run – this will be used with the --file directive to perform a test run without updating the database
- --create_table – create the users table. This will drop the table if it exists.
- --help – display the help menu.
    
### Database Connection [Configurable]
- -u – MySQL username
- -p – MySQL password
- -h – MySQL host
- -db – MySQL database name
    
>e.g. php user_upload.php --file file.csv --dry_run -u=user -p=password -h=localhost -db=test_db