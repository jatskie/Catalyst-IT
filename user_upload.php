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
$boolInvalidCommandFound = false;

if (count($arrArguments) == 0)
{
    recHelp('NO_DIRECTIVES');
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
            $mixUserData = processUsers($arrArgumentsContainer);
            if ($mixUserData)
            {
                // insert to database
                addUsers($mixUserData['valid'], $conn);
                showResult($mixUserData['valid'], $mixUserData['invalid'], $mixUserData['invalid_line_numbers']);
            }
            break;
        case '--help':
            return showHelpMenu();
            break;
        case '--create_table':
            return createTable($conn);
            break;
        default:
            $arrInvalidArg[] = $strArg;
            $boolInvalidCommandFound = true;
            break;
    }

    // no more arguments exit loop
    if ($strArg == null)
    {        
        break;
    }
}

if ($boolInvalidCommandFound)
{
    recHelp('INVALID_COMMAND');
}

/**
 * Make a database connection
 * 
 **/
function connectDB($aArrArgumentsContainer)
{
    $strServerName = 'localhost';
    $strUsername = 'root';
    $strPassword = "root";
    $strDbname = 'test_db';
    
    foreach ($aArrArgumentsContainer as $intIndex => $strValue)
    {
        $arrArgumentData = explode('=', $strValue);
        switch($arrArgumentData[0])
        {
            case '-u':
                if (isset($arrArgumentData[1]))
                {
                    $strUsername = $arrArgumentData[1];
                }
                else
                {
                    fwrite(STDOUT, "\r\n\r\n        Database username is being set but was empty. Ignoring option. \r\n\r\n");
                }
                break;
            case '-p':
                if (isset($arrArgumentData[1]))
                {
                    $strPassword = $arrArgumentData[1];
                }
                else
                {
                    fwrite(STDOUT, "\r\n\r\n        Database password is being set but was empty. Ignoring option.\r\n\r\n");
                }
                break;
            case '-h':
                if (isset($arrArgumentData[1]))
                {
                    $strServerName = $arrArgumentData[1];
                }
                else
                {
                    fwrite(STDOUT, "\r\n\r\n        Database host is being set but was empty. Ignoring option.\r\n\r\n");
                }
                break;
            case '-db':
                if (isset($arrArgumentData[1]))
                {
                    $strDbname = $arrArgumentData[1];
                }
                else
                {
                    fwrite(STDOUT, "\r\n\r\n        Database name is being set but was empty. Ignoring option.\r\n\r\n");
                }
                break;
            default:
                break;
        }
    }
    
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

        fwrite(STDOUT, "\r\n\r\n We encountered some issues with the database and cannot continue. \r\n". $strErrorMsg . "\r\n\r\n");
        recHelp();

        // print_r($e);
        // echo $e->getMessage();
        exit;
    }
}

/**
 * Create user table
 * 
 **/
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

/**
 * Validate and Insert data
 * 
 **/ 
function addUsers($aArrUserData, $aConnection)
{
    $sql = $aConnection->prepare("INSERT INTO users (name, surname, email) VALUES (?,?,?)");
    
    try {
        $aConnection->beginTransaction();

        foreach ($aArrUserData as $row)
        {
            $sql->execute($row);
        }

        $aConnection->commit();

    } catch (PDOException $e) {
        $aConnection->rollback();
        
        switch($e->errorInfo[1])
        {
            case 1062:
                $strErrorMsg = "Some users are already be in the database. Please check your CSV file. \r\n\r\n";
                break;
            case 1146:
            default:
                $strErrorMsg = "User table does not exists. \r\n\r\n";
                break;
        }

        fwrite(STDOUT, $strErrorMsg);
        recHelp();
        exit;
    }
}

/**
 * Get and parse csv file
 * 
 **/
function processUsers($aArrArgumentsContainer)
{
    $arrUsers = array();
    $arrInvalidData = array();
    $arrLineNumber = array();
    $arrEmailList = array();
    $boolIsDryRun = in_array('--dry_run', $aArrArgumentsContainer, true);

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
                    
                    $mixData = processCsv($line);
                    $strErrorMsg = ' (Invalid email)';
                    if ($mixData)
                    {
                        if (in_array($mixData[2], $arrEmailList))
                        {
                            $strErrorMsg = ' (Duplicate Entry)';
                            goto invalid;
                        }
                        else
                        {
                            $arrUsers[] = $mixData;
                            $arrEmailList[] = $mixData[2];
                        }
                    }
                    else
                    {
                        invalid:
                        $arrInvalidData[] = $line;
                        // Adding 1 because of zero-index
                        $arrLineNumber[] = ($intLineCtr + 1) . $strErrorMsg;
                    }

                    $intLineCtr++;
                }
                
                fclose($objFile);

                // check if this is a dry_run
                if ($boolIsDryRun)
                {
                    showResult($arrUsers, $arrInvalidData, $arrLineNumber);
                    return false;
                }
                
                // 
                return [
                    'valid' => $arrUsers,
                    'invalid' => $arrInvalidData,
                    'invalid_line_numbers' => $arrLineNumber
                ];
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
function processCsv($aArrCsvLine)
{
    // Csv row invalid
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
            $strValue = filter_var($strValue, FILTER_SANITIZE_EMAIL);
            $boolIsEmailValid = filter_var($strValue, FILTER_VALIDATE_EMAIL);

            if (false == $boolIsEmailValid)
            {
                return false;
            }
            else
            {
                $aArrCsvLine[$intIndex] = strtolower($strValue);
                continue;
            }
        }

        // name columns
        $aArrCsvLine[$intIndex] = ucwords(strtolower($strValue));
    }

    return $aArrCsvLine;
}

/**
 * Display Help Menu
 * 
 **/
function showHelpMenu()
{
    $strDirectives = "
    
    • --file [csv file name] – this is the name of the CSV to be parsed
    
    • --dry_run – this will be used with the --file directive to perform a test run without updating the database

    • --create_table – create the users table. This will drop the table if it exists.

    • --help – display the help menu.
    
    Database Connection [Configurable]

    • -u – MySQL username
    
    • -p – MySQL password
    
    • -h – MySQL host

    • -db – MySQL database name
    
    \033[32me.g. php user_upload.php --file file.csv --dry_run -u=user -p=password -h=localhost\033[37m
    ";

    fwrite(STDOUT, $strDirectives);
}

/**
 * Recommend help when issues are found
 * 
 **/
function recHelp($aStrType = 'SHOW_HELP')
{
    switch($aStrType)
    {
        case 'INVALID_COMMAND':
            $strMsg = 'Invalid command.';
            break;        
        case 'NO_DIRECTIVES':
            $strMsg = 'No directives found.';
            break;
        case 'SHOW_HELP':
        default:
            $strMsg = '';
            break;
    }

    fwrite(STDOUT, $strMsg . " Use --help to see available commands.\r\n\r\n");
} 

/**
 * Display results
 * 
 */
function showResult($aArrUsers, $aArrInvalidData, $aArrLineNumber)
{
    $intValidData = count($aArrUsers);
    $intInvalidData = count($aArrInvalidData);
    $strCsvLineNumber = implode(', ', $aArrLineNumber);

    $strResult = "
        Processing Finished
        ---------------------------------------------
        Valid Data: $intValidData
        Invalid Data: $intInvalidData
            Check csv line/s: $strCsvLineNumber
    ";
    fwrite(STDOUT, $strResult);
    exit;
}