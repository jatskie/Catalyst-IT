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
    recHelp();
    exit;
}

// Initiate the database connection
$conn = connectDB($arrArgumentsContainer);

while ($strArg = array_shift($arrArguments))
{
    switch ($strArg)
    {
        case '--file':            
            // process the csv
            return processUsers($arrArgumentsContainer);
            break;
        case '--help':
            return showHelpMenu();
            break;
        case '--create_table':
            return createTable($conn);
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
function connectDB($aArrArgumentsContainer)
{
    $strServerName = 'localhost';
    $strUsername = 'root';
    $strPassword = "root";
    $strDbname = 'test_db';
    

    try {
        $conn = new PDO("mysql:host=$strServerName; dbname=$strDbname", $strUsername, $strPassword);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;

    } catch(PDOException $e) {
        $intErrorCode = $e->errorInfo[1];
        switch($intErrorCode)
        {
            case 2002:
                $strErrorMsg = '    - Check that the server name is correct.';
                break;
            case 1045:
                $strErrorMsg = '    - Check that the server\'s username and password is correct.';
                break;
            case 1049:
                $strErrorMsg = '    - Database not found.';
                break;
            default:
                $strErrorMsg = '    - Something went wrong with the database connection.';
                break;
        }

        fwrite(STDOUT, "We encountered some issues with the database and cannot continue. \r\n". $strErrorMsg . "\r\n\r\n");
        recHelp('SHOW_HELP');

        // print_r($e);
        // echo $e->getMessage();
        exit;
    }
}
// Create user table
function createTable($aConnection)
{
    // sql to create table
    $sql = "DROP TABLE IF EXISTS users;
        CREATE TABLE users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        surname VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    ";
      
    // use exec() because no results are returned
    $aConnection->exec($sql);
    
    fwrite(STDOUT, 'User table created successfully');
    exit;
}
// Validate and Insert data

// Get and parse csv file
function processUsers($aArrArgumentsContainer)
{
    $arrUsers = array();
    $arrInvalidData = array();
    $arrLineNumber = array();
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
                        $intLineCtr++;
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
                        // Adding 1 because of zero-index
                        $arrLineNumber[] = ($intLineCtr + 1);
                    }

                    $intLineCtr++;
                }
                
                fclose($objFile);

                // check if this is a dry_run
                if ($boolIsDryRun)
                {
                    $intValidData = count($arrUsers);
                    $intInvalidData = count($arrInvalidData);
                    $strCsvLineNumber = implode(', ', $arrLineNumber);

                    $strResult = "
                        Processing Finished
                        ---------------------------------------------
                        Valid Data: $intValidData
                        Invalid Data: $intInvalidData
                            Check csv line/s: $strCsvLineNumber
                    ";
                    fwrite(STDOUT, $strResult);
                    return;
                }
                
                // 

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
        // remove leading and trailing whitespace
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

        // name columns
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

    • --create_table – create the users table. This will drop the table if it exists.
    
    Database Connection [required]

    • -u – MySQL username
    
    • -p – MySQL password
    
    • -h – MySQL host
    
    \033[32me.g. php user_upload.php --file file.csv --dry_run -u=user -p=password -h=localhost\033[37m
    ";

    fwrite(STDOUT, $strDirectives);
}

// Recommend help when issues are found
function recHelp($aStrType = 'NO_DIRECTIVES')
{
    $strMsg = '';
    switch($aStrType)
    {
        case 'SHOW_HELP':
            $strMsg = 'Use --help to see available commands.';
            break;
        case 'NO_DIRECTIVES':
        default:
            $strMsg = 'No directives found. You can use --help for valid directives.';
            break;
    }

    fwrite(STDOUT, $strMsg . "\r\n\r\n");
} 