# PHP version of the JSON checker. 
-	You will need to change the value of $url to your application's web path that 
contains your JSON APIs. __
-	Or pass your application's web path as value for query parameter 'url' 
http://localhost/json_checker/json_checker.php?url=http://localhost/bookstore/json 
-	Or, in command line, pass it as the first argument 
C:\wamp64\www\json_checker\> php json_checker.php http://localhost/bookstore/json 
        
testcases\ 

    in 
    
        Input for JSON API like name, password, isbn13 etc. 
        
    Out  
    
        Your expected output 
        
    Yours __
    
        Actual output generates by tested API 
        
Note 

- Tests are excuted in same order of file name in input folder. 
- Test will pass if API’s output is the same as your expected output (has all the correct JSON elements).
 Otherwise, if any part is wrong, the test will fail. 
- To avoid "invalid token" errors, you must successfully authenticate as one of the first tests (e.g 00-authenticate.txt). 
The json_checker will capture the token generated by the authenticate API and use if for all subsequent tests. 
For example, if you authenticate with apple.2016 user, any subsequent bootstrap test will fail due to an invalid token error (not admin token). 
- Please use the same folder structure (in, out) and file name conventions (e.g 300-read.txt  when creating your own tests.  
- To speed up your testing, use small bootstrap.zip files that have only the data needed to test your functions. 
Remember that the database is modified by all subsequent tests (e.g create, delete) until another bootstrap is done.


Changelog
- Revision 1 - initialize test cases 
- Current - Revision 1.1 - add upload test. Now Json output doesn't display index.


