<?php
// Change the value of $url to point to the web path that contains all your JSON APIs.
$url = 'http://localhost/json_checker/json/';

// The directories that contains the test cases, expected JSON output, and where to save the actual JSON output recieved
$directories = [
    'in' => 'testcases/in/',    // test cases
    'out' => 'testcases/out/',  // expected JSON output
    'yours' => 'testcases/yours/',  // where to save the actual JSON output recieved
];

// extension of JSON API
$json_ext =".php";




// Grab user's input of URL
if ( isset($_REQUEST['url'])) {
    // grab from query parameter if available.
    $url = $_REQUEST['url'];

} elseif ( isset($argv) && count($argv) > 1) {
    // grab from command line argument if available.
    $url = $argv[1];
}

// make sure URL ends with '/'
if ( !endsWith($url, '/')) {
    $url .= '/';
}

// clear yours directory of JSON responses 
createEmptyDirectory($directories['yours']);

// run the test cases
$test_results = runTestCases($url, $directories, $json_ext);

// display the results
displayResults($url, $test_results);


/* FUNCTIONS */

/**
 * Check a string ends with a specified string.
 * 
 * @param string $haystack  The string to search in.
 * @param string $end  The string to search for.
 * @return boolean.  If $haystack ends with $endStr, return true.  Otherwise, false.
 */
function endsWith($haystack, $end){
    $length = strlen($end);

    return $length === 0 || 
    (substr($haystack, -$length) === $end);
}

/**
 * Create an empty directory for the specified name.  
 * If directory exists, delete all the contents in the directory.
 * 
 * param string $directory The path of a directory
 */
function createEmptyDirectory($directory) {
    if ( !file_exists($directory)) {
        mkdir($directory);
    } else {
        $files = listFiles($directory);
        foreach ($files as $filename) {
            unlink( "{$directory}$filename");
        }
    }
}

/**
 * Get the list of filenames inside a directory.
 * 
 * @param string $directory  Name of a directory
 * 
 * @return array Filenames (string) of files inside the directory.  Sorted in alphabetical order, ascending.
 */
function listFiles($directory) {
    $filenames_arr = scandir($directory);
    //remove . and ..
    array_shift($filenames_arr);
    array_shift($filenames_arr);

    sort($filenames_arr);
    return $filenames_arr;    
}

/**
 * Save specified contents to a file.
 * 
 * @param string $directory The path of directory to save to
 * @param string $filename The name of the file to save to
 * @param string $contents The contents to save.
 */
function saveFile($directory, $filename, $contents) {
    file_put_contents( "$directory$filename", $contents);
}

/**
 * For use by jsons_are_equal().  Deep compare are two arrays for equality.
 * 
 * @param array $arr1  The array to compare
 * @param array $arr2  The other array to compare
 * @return boolean
 *  If the two arrays are equal, return true.  Otherwise, false.
 *  Two arrays are considered equal, if and only if,
 *  (1) Same key-and-value pairs.  For indexed array, this means the elements are in the same order.
 *  (2) If an element is an array,  call function arrays_are_equal() to compare the elements.
 *  (3) If an element is an object, call function jsons_are_equal() to compare the elements.  i.e. assume JSON object.
 *  (4) Otherwise, compare using ===.
 */
function arrays_are_equal($arr1, $arr2) {
    if ( count($arr1) != count($arr2) ) {
        return false;
    }

    foreach ($arr1 as $key => $value) {
        $type = gettype($value);
        if ( !isset( $arr2[$key] ) ) {
            return false;

        } else {
            $value2 = $arr2[$key];

            if ( $type === 'object') {
                if ( gettype($value2) !== 'object' || ! jsons_are_equal($value, $value2) ) {
                    return false;
                }

            } elseif ( $type === 'array') {
                if ( gettype($value2) !== 'array' || ! arrays_are_equal($value, $value2) ) {
                    return false;
                }

            } elseif ( $value !== $value2 ) {
                return false;
            }
        }

    }

    return true;
}

/**
 * Deep compare are two JSON objects for equality.
 * 
 * @param array $json1  The JSON object to compare
 * @param array $json2  The other JSON object to compare
 * @return boolean
 *  If the two JSON objects are equal, return true.  Otherwise, false.
 *  Two JSON objects are considered equal, if and only if,
 *  (1) Both are NOT null and ARE JSON objects.
 *  (2) Same key-and-value pairs.  
 *  (3) If an element is an array, call function arrays_are_equal() to compare the elements.
 *  (4) If an element is an object, call function jsons_are_equal() to compare the elements. 
 *  (5) Otherwise, compare using ===.
 */
function jsons_are_equal($json1, $json2) {
    if ( is_null($json1) || is_null($json2) || gettype($json1) != 'object' || gettype($json2) != 'object') {

        # handle special case where both json replies are JSON arrays and not JSON objects
		if (gettype($json1) == 'array' && gettype($json2) == 'array') {
		   if (arrays_are_equal($json1, $json2)) 
			   return true;  # arrays are equal
		   else  # arrays are not equal 
			   return false;   
		}  
		else   # one reply is an array the other is not. clear error
			return false;
		
		# main error of check. will return if either argument is null or either is not a JSON object
		return false;
    }

    foreach ($json1 as $key => $value) {
        $type = gettype($value);
        if ( !isset($json2->{$key}) ) {
			return false;

        } else{
            $value2 = $json2->{$key};

            if ( $type === 'object') {
                if ( gettype($value2) !== 'object' || ! jsons_are_equal($value, $value2) ) {
					return false;
                }

            } elseif ( $type === 'array') {
                if ( gettype($value2) !== 'array' || ! arrays_are_equal($value, $value2) ) {
					return false;
                }

            } elseif ( $value !== $value2 ) {
			    return false;

            }
        }
    }

    foreach ($json2 as $key => $value) {
        if ( !isset($json1->{$key}) ) {
				return false;
        }
	}  

    return true;
}

/**
 * @param string $url The URL for this request.
 * @param array $fields Associative array of HTTP form parameters.  
 * @param boolen $post If true, send HTTP POST.  Else send HTTP GET.  Default to false; i.e. HTTP GET
 * @param boolean $multipart Send a HTTP POST multipart-form request?  Default to false.
 * @return array Associative array
 *  For key 'response', string value is response obtained from this request.
 *  For key 'error', if set, string value is error message.
 */
function httpRequest($url, $fields = null, $post = false, $multipart = false) {
    
    // Send HTTP POST
    $ch=curl_init();
    #var_dump($url);
    #var_dump($url);
    if ( $post ) {
        // HTTP POST
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        /*
        Passing an array to CURLOPT_POSTFIELDS will encode the data as multipart/form-data, 
        while passing a URL-encoded string will encode the data as application/x-www-form-urlencoded.
        When testing with Tomcat, Java requires to use class MultipartFormDataRequest to handle multipart-form.
        */
        if ( $multipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields );
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields) );
        }    

    } else {
        // HTTP GET
        $query_data =  http_build_query($fields);
        curl_setopt($ch, CURLOPT_URL, "$url?$query_data");
        curl_setopt($ch, CURLOPT_POST, false);        

    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
    
    // Get URL content
    do {
        $redirect = false;

        $response = curl_exec($ch);    
        $httpResult = [ 'response' => $response ];
        if ($response === false) { 
            // typically, wrong host
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $httpResult['error'] = "Unable to connect $url.  Error #$errno: '$error'";

        } else {

            $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            if ( !empty($redirect_url)) {
                // Typically, HTTP Code 302 - URL redirection
                curl_setopt($ch, CURLOPT_URL, $redirect_url);
                $redirect = true;

            } else {
                // Check HTTP code
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    
                if ($httpCode >= 400 ) { 
                    // HTTP 4xx are client errors
                    // HTTP 5xx are server errors
                    // Typically,  HTTP CODE 404 Not found; path not found.

                    $httpResult['error'] = "Cannot connect to $url.  HTTP CODE $httpCode";
                } 
            }
        }
    } while ($redirect === true);

    // close handle to release resources
    curl_close($ch);

    return $httpResult;
}

/**
 * Do authentication test case.
 * 
 * @param string $json_api_url The URL of the JSON API
 * @param string $directories  Associative array of directories' paths.
 * @param string $filename  The name of file for this test case.
 * 
 * @return array Associative array
 *  (1) 'token' - set when  test case passed and 'status' is 'success' (i.e. test successful login), string value is the token.  
 *  (2) 'result' - if test case passed, boolean value is true.  Otherwise, boolean value is false.
 *  (3) 'error' - set when there is network connection (cURL) error, string value is error message.
 */
function doAuthenticate($json_api_url, $directories, $filename) {
    // get inputs
    /* BUG FIX
    Date: 20181031
    WAS: $fields = parse_ini_file( "{$directories['in']}$filename");
    DESC: http://php.net/manual/en/function.parse-ini-file.php
        Character ! have a special meaning in the value for parse_ini_file()
    FIX: Use INI_SCANNER_RAW to  parse_ini_file() to read the value as it is.
        Characters ; " \ are still special characters for parse_ini_file().  They need to be in double quotes.
        key=";"\"
    */
    $fields = parse_ini_file( "{$directories['in']}$filename", false, INI_SCANNER_RAW);

    // Send HTTP POST
    $httpResult = httpRequest($json_api_url, $fields, true);

    $response = $httpResult['response'];
    #var_dump($response);
    $testResult = [];
    if ( isset($httpResult['error']) ) {
        $testResult['error'] = $httpResult['error'];
    }

    if ($response === false) {
        $testResult['result'] = false;
        return $testResult;
    }

    // save the response
    saveFile( $directories['yours'], $filename, $response);

    // check response
    $response = @json_decode($response);
    $answer = @json_decode(file_get_contents( "{$directories['out']}$filename"));
    $a = isset($response->status);
    if ( isset($response->status) && @$response->status == @$answer->status ) {
        
        if ( $answer->status == 'error' ) {
            /* BUG FIX
            Date: 20181101
            WAS: $testResult['result'] = true;
            DESC: Forget to check the error messages.
            FIX:  Add code to compare the json objects.
            */
            $testResult['result'] = jsons_are_equal($response, $answer);
            return $testResult;
            
        } elseif (  $answer->status == 'success' && isset($response->token ) && strlen($response->token) > 0) {
            $testResult['token'] = $response->token;
            $testResult['result'] = true;
            return $testResult;
        }
    }

    $testResult['result'] = false;
    return $testResult;
}

/**
 * Do test cases (bootstrap or upload additional data) that uploads a zipped file.  
 * 
 * @param string $json_api_url The URL of the JSON API
 * @param string $directories  Associative array of directories' paths.
 * @param string $filename  The name of file for this test case.
 * 
 * @return array Associative array
 *  (1) 'result' - boolean
 *      a.  If test case passed, boolean value is true.
 *      b.  Otherwise, boolean value is false.
 *  (2) 'error' - set when there is network connection (cURL) error, string value is error message.
 */
function doZipUpload($json_api_url, $directories, $filename, $token) {
    $zipfile_path = getcwd() . "/{$directories['in']}$filename";
    
    if (!empty($token))
        $fields['token'] = $token;            
    
    $fields['bootstrap-file']=  new CURLFile($zipfile_path, 'application/zip', $filename);
    
    #var_dump($json_api_url);
    #var_dump($fields['bootstrap-file']);
    // Send HTTP POST
    $httpResult = httpRequest($json_api_url, $fields, true, true);
    $response = $httpResult['response'];

    #var_dump($response);
    $testResult = [];
    if ( isset($httpResult['error']) ) {
        $testResult['error'] = $httpResult['error'];
    }
    
    if ($response === false) {
        $testResult['result'] = false;
        return $testResult;
    }
    
    // save the response    
    $filename_txt = str_ireplace('.zip', '.txt', $filename);
    saveFile( $directories['yours'], $filename_txt, $response);

    // check response
    $response = @json_decode($response);
    $answer = @json_decode(file_get_contents( "{$directories['out']}$filename_txt"));

    $testResult['result'] = jsons_are_equal($response, $answer);
    return $testResult;    
}

/**
 * Do test cases (that are not authenticate, bootstrap, upload additional data).  
 * 
 * @param string $json_api_url The URL of the JSON API
 * @param string $directories  Associative array of directories' paths.
 * @param string $filename  The name of file for this test case.
 * 
 * @return array Associative array
 *  (1) 'result' - boolean
 *      a.  If test case passed, boolean value is true.
 *      b.  Otherwise, boolean value is false.
 *  (2) 'error' - set when there is network connection (cURL) error, string value is error message.
 */
function doGet($json_api_url, $directories, $filename, $token) {
    // get inputs
    /* BUG FIX
    Date: 20181031
    WAS: $fields = parse_ini_file( "{$directories['in']}$filename");
    DESC: http://php.net/manual/en/function.parse-ini-file.php
        Character ! have a special meaning in the value for parse_ini_file()
    FIX: Use INI_SCANNER_RAW to  parse_ini_file() to read the value as it is.
        Characters ; " \ are still special characters for parse_ini_file().  They need to be in double quotes.
        key=";"\"
    */
    $fields = parse_ini_file( "{$directories['in']}$filename", false, INI_SCANNER_RAW);
    
    if (!empty($token))
        $fields['token'] = $token;

    // Send HTTP GET
    $httpResult = httpRequest($json_api_url, $fields); 
    $response = $httpResult['response'];
    
    $testResult = [];
    if ( isset($httpResult['error']) ) {
        $testResult['error'] = $httpResult['error'];
    }
    
    if ($response === false) {
        $testResult['result'] = false;
        return $testResult;
    }
    
    // save the response
    saveFile( $directories['yours'], $filename, $response);

    // check response
    $response = json_decode($response);
    $answer = @json_decode(file_get_contents( "{$directories['out']}$filename"));

    $testResult['result'] = jsons_are_equal($response, $answer);
    return $testResult;    
}

/**
 * Display the results of one test case.
 * 
 * @param array $testResult  Associative arrayswith three keys
 *  (1)  'num' - The test case num
 *  (2)  'json api' - The JSON API tested
 *  (3)  'result' - Associative array
 *              a.  If test case passed, boolean value is true.
 *              b.  Otherwise, boolean value is false.
 *  (4)  'error' - Any error encountered connecting to JSON API.
 */
function displayTestResult($testResult) {
        $result = $testResult['result'] ? 'pass' : 'fail';
        echo "
            Test {$testResult['num']} '{$testResult['json api']}' $result
        ";
        if ( isset( $testResult['error']) ) {
            echo "
                [Error] {$testResult['error']}
            ";
        }
}

/**
 * Display the results of the test cases.
 * 
 * @param string $url  
 *  The URL of the web path that contains all the JSON services. 
 *  E.g. http://localhost/spm/json/
 * 
 * @param array $test_results  Indexed array whose elements are associative arrays.
 *  Each element (associative array) has three keys
 *  (1)  'num' - The test case num
 *  (2)  'json api' - The JSON API tested
 *  (3)  'result' - Associative array
 *              a.  If test case passed, boolean value is true.
 *              b.  Otherwise, boolean value is false.
 *  (4)  'error' - Any error encountered connecting to JSON API.
 */
function displayResults($url, $test_results) {
    echo "
    <html>
    <body>
        <p>$url</p>
        <pre>
    ";

    $num_tests = count($test_results);
    $num_passed = 0;
    foreach($test_results as $testResult) {
        displayTestResult($testResult);

        if ( $testResult['result'] ) $num_passed++;
    }
    
    echo "
        Score: $num_passed / $num_tests
        </pre>
    </body>
    </html>
    ";
}


/**
 * Execute test cases
 * 
 * @param string $url  
 *  The URL of the web path that contains all the JSON services. 
 *  E.g. http://localhost/spm/json/
 * 
 * @param array $directories  
 *  Associative array 
 *  (1) key 'in' whose value is path of directory containing test-case files 
 *      'in' => 'testcases/in/'
 *      (a)  There are only text or zipped files in this directory.
 *      (b)  The filenames are of the following format:
 * 
 *              [number]-[json api name].txt  or [number]-[json api name].zip
 * 
 *           E.g. 01-simple-service.txt, 03-bootstrap.zip
 * 
 *          1.  'authenticate' is the JSON API for authentication.  
 *              It is expected to return a 'status' JSON field.  
 *              If successful authentication, 'status' has value 'success' and there will be a JSON field 'token' .
 *          2.  The token will be part of the query parameters for all other JSON APIs.
 * 
 *      (c)  For text files (.txt), it contains lines of [name]=[value] which are the query parameters.
 * 
 *      (d)  For zipped files, the file will be uploaded to the JSON API which should be either bootstrap or upload additional data.
 * 
 *  (2) key 'out' whose value is path of directory containing files that contains the expected (aka correct) JSON output
 *      'out' => 'testcases/out/'
 * 
 *  (3) key 'yours' whose value is path of directory where the actual JSON output recieved will be saved to
 *      'yours' => 'testcases/yours/',
 * 
 * @param string $json_ext Extension of the JSON API.  E.g. ".php"
 * 
 * @return array Test cases' results.  This is an indexed array whose elements are associative array.
 *  Each element (associative array) has four keys
 *  (1)  'num' - The test case num
 *  (2)  'json api' - The JSON API tested
 *  (3)  'result' - Associative array
 *              a.  If test case passed, boolean value is true.
 *              b.  Otherwise, boolean value is false.
 *  (4)  'error' - Any error encountered connecting to JSON API.
 */
function runTestCases($url, $directories, $json_ext) {

    // get the list of input files
    $in_files = listFiles($directories['in']);

    $token = ''; // to keep token returned after a successful authentication
    $test_results = [];

    // for each test cases
    foreach ($in_files as $filename) {
        // parse the filename for the test case num, JSON call and file extension
        $parts = explode('-', $filename, 2);
        $test_case_num = $parts[0];

        $parts = @explode('.', $parts[1], 2);
        $json_api = @$parts[0];
        $file_ext = @$parts[1];

        if ( strcasecmp( $file_ext, 'zip') == 0) {
            // This is a zip file.  Need to upload file
            #var_dump($token);
            $testResult = doZipUpload("$url$json_api$json_ext", $directories, $filename, $token);

        } else { // .txt files
            
            if ($json_api == 'authenticate') {
                // authenticate
                $testResult = doAuthenticate("$url$json_api$json_ext", $directories, $filename);
                if ( isset($testResult['token'])) {
                    $token = $testResult['token'];
                }

            } else {
                
                // invoke JSON API
                $testResult = doGet("$url$json_api$json_ext", $directories, $filename, $token);
            }
        }

        // store this test case' result
        $testResult['num'] = $test_case_num;
        $testResult['json api'] = $json_api;
        $test_results[] = $testResult;
    }

    return $test_results;
}