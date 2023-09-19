<?php
/*** 
Create a PHP script, that is executed from the command line, which accepts a CSV file as input (see command line directives below) and processes the CSV file. The parsed file data is to be inserted into a MySQL database. A CSV file is provided as part of this task that contains test data, the script must be able to process this file appropriately.

The PHP script will need to correctly handle the following criteria:

• CSV file will contain user data and have three columns: name, surname, email (see table definition below)
• CSV file will have an arbitrary list of users
• Script will iterate through the CSV rows and insert each record into a dedicated MySQL database into the table “users”
• The users database table will need to be created/rebuilt as part of the PHP script. This will be defined as a Command Line directive below
• Name and surname field should be set to be capitalised e.g. from “john” to “John” before being inserted into DB
• Emails need to be set to be lower case before being inserted into DB
• The script should validate the email address before inserting, to make sure that it is valid (valid means that it is a legal email format, e.g. “xxxx@asdf@asdf” is not a legal format). In case that an email is invalid, no insert should be made to database and an error message should be reported to STDOUT.

We are looking for a script that is robust and gracefully handles errors/exceptions. The PHP script command line argument definition is outlined in 1.4 Script Command Line Directives. However, user documentation will be looked upon favourably.

DIRECTIVES
The PHP script should include these command line options (directives):

• --file [csv file name] – this is the name of the CSV to be parsed
• --create_table – this will cause the MySQL users table to be built (and no further action will be taken)
• --dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered
• -u – MySQL username
• -p – MySQL password
• -h – MySQL host
• --help – which will output the above list of directives with details.

*/

// Get, parse, validate cli arguments 

// Exclude the filename of this script
array_shift($argv);

$arrArguments = $arrArgumentsContainer = $argv;
$arrInvalidArg = array();

if (count($arrArguments) == 0)
{
    fwrite(STDOUT, 'No directives found. You can use --help for valid directives.');
    exit(0);
}

while ($strArg = array_shift($arrArguments))
{
    switch ($strArg)
    {
        case '--file':
            return processUsers($arrArgumentsContainer);
            break;
        case '--help':
            return showHelpMenu();
            break;
        default:
            $arrInvalidArg[] = $strArg;

            break;
    }

    // no more arguments exit loop
    if ($strArg == null)
    {        
        exit;
    }
}

// Make a database connection

// Create user table

// Validate and Insert data

// Get and parse csv file
function processUsers($aArrArgumentsContainer)
{
    $arrUsers = array();
    $arrInvalidData = array();
    $boolIsDryRun = in_array('--dry_run', $aArrArgumentsContainer);

    // find the csv file in the arguments
    foreach ($aArrArgumentsContainer as $intIndex => $strValue) 
    {
        if (substr($strValue, -4) === '.csv')
        {
            // check if file exists
            if (file_exists($strValue))
            {
                // convert file into array
                $objFile = fopen($strValue, 'r');
                $intLineCtr = 0;
                while(($line = fgetcsv($objFile)) !== false)
                {
                    // skip column labels
                    if ($intLineCtr == 0)
                    {
                        $intLineCtr = 1;
                        continue;
                    }
                    
                    $mixData = processData($line);
                    if ($mixData)
                    {
                        $arrUsers[] = $mixData;
                    }
                    else
                    {
                        $arrInvalidData[] = $line;
                    }
                }

                // check if this is a dry_run
                if ($boolIsDryRun)
                {
                    $intValidData = count($arrUsers);
                    $intInvalidData = count($arrInvalidData);
                    $strResult = "
                        Processing Finished.

                        Valid Data: $intValidData
                        Invalid Data: $intInvalidData

                    ";
                    fwrite(STDOUT, $strResult);
                    return;
                }

                return;
            }
            else
            {
                fwrite(STDOUT, 'File does not exist.');
                return;
            }
        }
    }

    fwrite(STDOUT, 'Please provide a file to be uploaded.');
}

/**
 * Check if email is valid
 * Capitalize Name and Surname
 * 
 * @return processed array or false if email is invalid
 *
 */
function processData($aArrCsvLine)
{
    if (count($aArrCsvLine) > 3)
    {
        return false;
    }

    foreach ($aArrCsvLine as $intIndex => $strValue)
    {
        // remove leading and tail whitespae
        $strValue = trim($strValue);

        // email column
        if ($intIndex == 2)
        {
            $boolIsEmailValid = filter_var($strValue, FILTER_VALIDATE_EMAIL);

            if (false == $boolIsEmailValid)
            {
                return false;
            }
            else
            {
                $aArrCsvLine[$intIndex] = strtolower($strValue);
            }
        }

        // name column
        $aArrCsvLine[$intIndex] = ucwords(strtolower($strValue));
    }

    return $aArrCsvLine;
}

// Display Help Menu
function showHelpMenu()
{
    $strDirectives = "
    
    • --file [csv file name] – this is the name of the CSV to be parsed
    
    • --dry_run – this will be used with the --file directive to perform a test run without updating the database

    • --create_table – create the users table. This will fail to execute if table is already existing.

    • --drop_table – delete the users table. Use with extreme caution. Data deleted will no longer be available.  
    
    Database Connection [required]

    • -u – MySQL username
    
    • -p – MySQL password
    
    • -h – MySQL host
    
    \033[32me.g. php user_upload.php --file file.csv --dry_run -u=user -p=password -h=localhost\033[37m
    ";

    fwrite(STDOUT, $strDirectives);
}