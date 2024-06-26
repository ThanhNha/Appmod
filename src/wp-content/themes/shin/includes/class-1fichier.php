<?php

if( ! defined( 'ABSPATH' ) ) die ( '✋' );

class fichier {
    /**
     * @var Client
     */
    private $client;
    /**
     * @var
     */
    public $error;

    /**
     * fichier constructor.
     * @param $token
     */
    public function __construct()
    {
                
        require_once TEMPLATEPATH . '/includes/google-api-php-client-master/vendor/autoload.php';
        
        $token = appyn_options( '1fichier_apikey' );


        $this->client = new GuzzleHttp\Client([
            'base_uri' => 'https://api.1fichier.com/v1/',
            'http_errors' => false,
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

    }

    /**
     * @param $json
     * @param $uri
     * @return bool|mixed
     */
    public function request($json, $uri)
    {
        try {
            $response = $this->client->request('POST', $uri, [
                'json'  =>   $json
            ]);
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            $this->error = $e;
            return false;
        }

        $output = json_decode($response->getBody()->getContents(), true );

        if( $output['status'] == "KO" ) {
            throw new Exception($output['message']);
        }

        sleep(2);
        
        return $output;
    }

    /**
     * @param $uri
     * @return bool|mixed
     */
    public function download($uri)
    {
        $json = [
            "url"       =>  $uri,
            "pretty"    =>  1
        ];
        return $this->request($json,"download/get_token.cgi");
    }

    /**
     * @param $folder_id
     * @return bool|mixed
     */
    public function file_ls($folder_id = 0)
    {
        $json = [
            "folder_id"     =>  $folder_id,
            "pretty"        =>  1
        ];
        return $this->request($json, "file/ls.cgi");
    }

    /**
     * @param $url
     * @return bool|mixed
     */
    public function file_info($url)
    {
        $json = [
            "url"       =>  $url,
            "pretty"    =>  1
        ];
        return $this->request($json, "file/info.cgi");
    }

    /**
     * @param $url
     * @return bool|mixed
     */
    public function av_scan($url)
    {
        $json = [
            "url"       =>  $url,
            "pretty"    =>  1
        ];
        return $this->request($json, "file/scan.cgi");
    }

    /**
     * @param $urls
     * @return bool|mixed
     */
    public function file_rm($urls)
    {
        if(is_array($urls)){
            foreach($urls as $url){
                $files []= array("url"   =>  $url);
            }
        }else{
            $files []= array("url"  =>  $urls);
        }
        if (!empty($files)) {
            $json = [
                "files"   =>  $files
            ];
        }else{
            return false;
        }
        return $this->request($json, "file/rm.cgi");
    }

    /**
     * @param $urls
     * @param $folder_id
     * @return bool|mixed
     */
    public function file_mv($urls, $folder_id)
    {
        if(!is_array($urls)){
            $urls = [$urls];
        }
        $json = [
            "pretty"                    =>  1,
            "destination_folder_id"     =>  $folder_id,
            "urls"                      =>  $urls
        ];

        return $this->request($json, "file/mv.cgi");
    }

    /**
     * @param $urls
     * @param $folder_id
     * @return bool|mixed
     */
    public function file_cp($urls, $folder_id)
    {
        if(!is_array($urls)){
            $urls = [$urls];
        }
        $json = [
            "pretty"                    =>  1,
            "destination_folder_id"     =>  $folder_id,
            "urls"                      =>  $urls
        ];

        return $this->request($json, "file/cp.cgi");
    }

    public function file_rn($url, $filename)
    {
        $json = [
            "pretty"    =>  1,
            "urls"      =>  [$url],
            "filename"  =>  $filename
        ];
        return $this->request($json, "file/chattr.cgi");
    }

    public function upload_token()
    {
        $json = [
            "pretty"    =>  1
        ];
        return $this->request($json, "upload/get_upload_server.cgi");
    }

    public function upload_file($filepath, $to = 0, $domain = 0)
    {
        $server = $this->upload_token();
        try {
            $response = $this->client->request('POST', "https://" . $server["url"] . "/upload.cgi?id=" . $server["id"], [
                'multipart' => [
                    [
                        'name' => "file[]",
                        'contents' => fopen($filepath, 'r'),
                        'did' => $to,
                        'domain' => $domain
                    ]
                ]
            ]);
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            $this->error = $e;
            return false;
        }
        $body = $response->getBody()->getContents();
        if( preg_match('/Upload finished|Envoi terminé/ms', $body)){
            
            try {
                $response = $this->client->request('POST', "https://" . $server["url"] . "/end.pl?xid=" . $server["id"], [
                    'headers' => [
                        "JSON" => 1
                    ]
                ]);
            } catch (GuzzleHttp\Exception\GuzzleException $e) {
                $this->error    =   $e;
                return false;
            }
            
            return json_decode($response->getBody()->getContents(), true);
        }
    }

    /**
     * @param $folder_id
     * @return bool|mixed
     */
    public function folder_ls($folder_id = 0)
    {
        $json = [
            "folder_id"     =>  $folder_id,
            "pretty"        =>  1
        ];
        return $this->request($json, "folder/ls.cgi");
    }

    /**
     * @param $name
     * @param bool $folder_id
     * @param bool $sharing_user
     * @return bool|mixed
     */
    public function mkdir($name, $folder_id = false, $sharing_user = false)
    {
        $json = [
           "name"                   =>  $name,
        ];
        if($folder_id){
            $json["folder_id"]      =   $folder_id;
        }
        if($sharing_user){
            $json["sharing_user"]   =   $sharing_user;
        }

        return $this->request($json, "folder/mkdir.cgi");
    }

    /**
     * @param $folder_id
     * @param int $share
     * @param bool $pass
     * @param bool $shares
     * @return bool|mixed
     */
    public function folder_share($folder_id, $share = 0, $pass = false, $shares = false)
    {
        $json = [
            "folder_id"     =>  $folder_id,
            "share"         =>  $share
        ];
        if($pass){
            $json["pass"]   =   $pass;
        }
        if($shares){
            $json["shares"] =   $shares;
        }

        return $this->request($json, "folder/share.cgi");
    }

    /**
     * @param $email
     * @param int $rw
     * @param int $hide_links
     * @param bool $add_array
     * @return array
     */
    public function folder_share_gen($email, $rw = 0, $hide_links = 0, $add_array = false)
    {
        $json = [
            "email"         =>  $email,
            "rw"            =>  $rw,
            "hide_links"    =>  $hide_links
        ];
        if(is_array($add_array)){
            $array[] = $add_array;
        }
        $array[] = $json;
        return $array;
    }

    /**
     * @param $folder_id
     * @param $destination_folder_id
     * @param bool $destination_user
     * @return bool|mixed
     */
    public function folder_mv($folder_id, $destination_folder_id, $destination_user = false)
    {
        $json = [
            "folder_id"                 =>  $folder_id,
            "destination_folder_id"     =>  $destination_folder_id
        ];
        if($destination_user){
            $json["destination_user"]   =   $destination_user;
        }
        return $this->request($json, "folder/mv.cgi");
    }

    /**
     * @param $folder_id
     * @return bool|mixed
     */
    public function folder_rm($folder_id)
    {
        $json = [
            "folder_id"     =>  $folder_id
        ];
        return $this->request($json, "folder/rm.cgi");
    }

    public function ftp_process()
    {
        $json = [
            "pretty"        =>  1
        ];
        return $this->request($json, "ftp/process.cgi");
    }

    public function ftp_user_ls()
    {
        $json = [
            "pretty"        =>  1
        ];
        return $this->request($json, "ftp/users/ls.cgi");
    }

    public function ftp_user_add($username, $password, $folder_id)
    {
        $json = [
            "user"          =>  $username,
            "pass"          =>  $password,
            "folder_id"     =>  $folder_id
        ];
        return $this->request($json, "ftp/users/add.cgi");
    }

    public function ftp_user_rm($username)
    {
        $json = [
            "user"          =>  $username
        ];
        return $this->request($json, "ftp/users/rm.cgi");
    }

    public function remote_ls()
    {
        $json = [
            "pretty"        =>  1
        ];
        return $this->request($json, "remote/ls.cgi");
    }

    public function remote_info($id)
    {
        $json = [
            "id"            =>  $id
        ];
        return $this->request($json, "remote/info.cgi");
    }

    public function remote_request($urls, $folder_id, $headers = false)
    {
        $json = [
            "urls"          =>  $urls,
            "folder_id"     =>  $folder_id,
        ];
        if($headers){
            $json["headers"] = $headers;
        }
        return $this->request($json, "remote/request.cgi");
    }

    /**
     * checksum parser
     *
     * checksum ``openssl dgst -whirlpool * > all.checksum``
     * checksum_parser("all.checksum") fullpath or __DIR__ / all.checksum
     * [
     *   ["filename", "hash"],
     *   ["filename", "hash"],
     *   ...
     * ]
     *
     * @param $filename
     * @return bool | array
     */
    public function checksum_parser($filename){
        if(!file_exists($filename)){
            $filename = __DIR__ . "/" . $filename;
            if(!file_exists($filename)){
                return false;
            }
        }
        $body = file_get_contents($filename);
        $checksums = array_values(array_filter(explode("\n", $body), "strlen"));
        $parser = [];
        foreach($checksums as $id => $checksum){
            if(preg_match('|whirlpool\(([^)]*)\)= (.*)$|', $checksum, $match)){
                $parser[$match[2]] = $match[1];
            }
        }

        return $parser;
    }

    /**
     * checksum_check
     *
     * checksum file path and how to make checksum view checksum_parse doc
     * find checksum from folder
     * if find all checksum to return true
     * $verbose true to show OK or NOT
     *
     * When not find checksum return not fond file and checksum
     * return array format is same as checksum_parser
     *
     *
     * @param $filename
     * @param $folder_id
     * @param bool $verbose
     * @return array|bool
     */
    public function checksum_check($filename, $folder_id, $verbose = false){
        $files = $this->file_ls($folder_id)["items"];
        if(!$files){
            $this->error = "checksum_check: folder_id is notfound or connection error";
            return false;
        }
        $checksums = $this->checksum_parser($filename);

        $errors = [];

        foreach($checksums as $checksum => $name){

            $bool = false;
            foreach($files as $file){
                if($file["checksum"] === $checksum){
                    $bool = true;
                }
            }
            if(!$bool){
                $errors[$checksum] = $name;
                if($verbose){
                    echo "NOT " . $name . "\n";
                }
            }else{
                if($verbose){
                    echo "OK  " . $name . "\n";
                }
            }
        }
        if(empty($errors)){
            return [];
        }else{
            return $errors;
        }
    }

    /**
     * @param $checksum_path
     * @param $folder_id
     * @return array
     */
    public function checksum_diff($checksum_path, $folder_id){
        return array_diff($this->checksum_parser($checksum_path), $this->checksum_check($checksum_path, $folder_id));
    }

    
    public function duplicate_delete($folder_id, $filename) {

        foreach($this->file_ls($folder_id)["items"] as $video) {
            if($video["filename"] == $filename) {
                $this->file_rm($video["url"]);
                return;
            }
        }	
    }

    public function Upload( $file, $filename ) {
        
        $this->duplicate_delete( 0, $filename );

        $u = $this->upload_file( $file );

        if( $u ) {
            return array( 'url' => $u['links'][0]['download'] );
        }
    }
}