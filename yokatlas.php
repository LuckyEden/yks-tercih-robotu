<?php
    error_reporting(E_ERROR | E_PARSE);
    class YOKATLAS{
        public $columnVars;
        public $defaults;
        public $res;
        public $ds;


        function __construct($ds){
            $this->ds = $ds;

            $this->columnVars = array(
                'yop_kodu' => 1, 
                'uni_adi' => 2,
                'program_adi' => 4,
                'sehir_adi' => 6,
                'universite_turu' => 7,
                'ucret_burs' => 8,
                'ogretim_turu' => 9,
                'doluluk' => 14
            );
            

            $this->defaults = array(
                'draw'  => 1, 
                'start' => 0,
                'length' => 100,
                'puan_turu' => 'dil',
                'ust_bs' => 0,
                'alt_bs' => 3000000,
                'yeniler' => 1,
                'ust_puan' => 500,
                'alt_puan' => 150
            );

            $json_object = file_get_contents("columnData.json", true);
            $json_data = json_decode($json_object,true);

            if($ds['puan_turu'] == 'tyt'){
                $new_url = $this->GetURI($ds, $json_data[0], $this->columnVars, $this->defaults);
                $url = 'https://yokatlas.yok.gov.tr/server_side/server_processing-atlas2016-TS-t3.php';

                $this->res = json_decode( $this->httpPost($url, $new_url) );
                
                # $this->parse()
            }else{
                $new_url = $this->GetURI($ds, $json_data[1], $this->columnVars, $this->defaults);
                $url = 'https://yokatlas.yok.gov.tr/server_side/server_processing-atlas2016-TS-t4.php';

                $this->res = json_decode( $this->httpPost($url, $new_url) );

                # $this->parse()
            }
        }

        function GetURI($ds, $json_data, $columnVars, $defaults){
            $url_parsed = parse_url("?" . $json_data);
            $result = "";
            parse_str($url_parsed['query'], $result);

            foreach (array_keys($columnVars) as &$key){
                try{
                    if($ds[$key]){
                        $result['columns['. $columnVars[$key] .'][search][value]'] = $ds[$key];
                    }
                }catch(err){

                }
            }


            foreach (array_keys($defaults) as &$key){
                try{
                    if($ds[$key] == null){
                        
                        $result[$key] = $defaults[$key];
                        continue;
                    }
                }catch(err){
                    $result[$key] = $defaults[$key];
                    continue;
                }

                if($key == "puan_turu"){
                    if($ds[$key] == "say" || $ds[$key] == "söz" || $ds[$key] == "dil" || $ds[$key] == "ea")
                    {
                        $result[$key] = $ds[$key];
                    }else{
                        $result[$key] = $defaults[$key];
                    }

                    continue;
                }

                if($key == "search"){
                    $val = $ds[$key];
                    if($val == null){
                        $val == "";
                    }

                    $result["search[value]"] = $val;
                    $result["search[regex]"] = false;
                    return;
                }

                $result[$key] = $ds[$key];
            }
            
            return http_build_query($result);
        }

        function httpPost($url, $data)
        {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        }

        function getTbs($str){
            
            $str = str_replace(",",".",$str);
            $matches = array();
            preg_match("/[-+]?(?:\d*\.\d+.\d+|\d+)/", $str, $matches, PREG_OFFSET_CAPTURE);
            return $matches[0];
        }

        function getKontenjanNumber($str){
            $matches = array();
            preg_match("/\d{0,1000}[+]\d{0,10}/", $str, $matches, PREG_OFFSET_CAPTURE);
            return $matches[0];
        }

        function getYerlesen($str){
            
            $matches = array();
            preg_match("/(.*\d+\d+|\d+)/", $str, $matches, PREG_OFFSET_CAPTURE);
            return $matches[0];
        }

        function getYopKodu($str){
            $matches = array();
            return $str;
            #preg_match_all(`/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i`, $str, $matches);
            #return json_encode($matches);
            #return $matches["href"][0];
        }

        function getTqText($string){
            return trim($string);
        }

        function getYopId($str){
            $matches = array();
            preg_match_all(`/<a[^>]*href=["']([^"']*)["']/`, $str, $matches);
            return json_encode($matches);
        }

        function getOgretimDili($str){
            $matches = array();
            preg_match_all('/\((.*?)\)/', $str, $matches);
            $s = $matches[1][0];
            if($s !== "İngilizce" && $s!== "Arapça" && $s !== "Fransızca" && $s !== "Rusça" && $s !== "Almanca"){
                $s = "Türkçe";
            }

            return $s;
        }

        function parse(){
            $p_datas = array();
            
            $datas = $this->res->data;

            if($this->ds['puan_turu'] == "tyt"){
                foreach($datas as $_){
                    $n_data = array(
                        "yop_kodu" => $_[1],
                        "universite"=> $this->getTqText($_[33]),
                        "fakulte"=> $_[3],
                        "program_adi"=> $_[34],
                        "sehir_adi"=> $this->getTqText($_[6]),
                        "universite_turu"=> $_[7],
                        "ucret_burs"=> $_[8],
                        "ogretim_turu"=> $_[9],
                        "doluluk"=> $_[14],
                        "ogretim_dili" => $this->getOgretimDili($_[5]),
                        "yerlesen"=> [
                            
                            ltrim( $this->getYerlesen($_[16])[0], "\n\t\0"),
                            $_[17],
                            $_[18]
                        ],
                        "kontenjan"=> [
                            $this->getKontenjanNumber($_[10])[0],
                            $_[11],
                            $_[12],
                            $_[13]
                        ],
                        "tbs"=> [
                            $this->getTbs($_[22])[0] == null ? "Dolmadı" : $this->getTbs($_[22])[0]
                        ],
                        "taban"=> [
                            $_[30] == null ? "---" : $_[30],
                            $_[32]
                        ]
                    );
                    
                    array_push($p_datas, $n_data);
                    #echo json_encode($p_datas);
                }
                usort($p_datas, function($a, $b){
               
                    return strcmp($b['taban'][0], $a['taban'][0]);
                });

                return $p_datas; 
            }else{
                foreach($datas as $_){
                    $n_data = array(
                        "yop_kodu" => $_[1],
                        "universite"=> $this->getTqText($_[41]),
                        "fakulte"=> $_[3],
                        "program_adi"=> $_[42],
                        "sehir_adi"=> $this->getTqText($_[6]),
                        "universite_turu"=> $_[7],
                        "ucret_burs"=> $_[8],
                        "ogretim_turu"=> $_[9],
                        "doluluk"=> $_[14],
                        "ogretim_dili" => $this->getOgretimDili($_[5]),
                        "yerlesen"=> [
                            
                            ltrim( $this->getYerlesen($_[15])[0], "\n\t\0"),
                            $_[17],
                            $_[18]
                        ],
                        "kontenjan"=> [
                            $this->getKontenjanNumber($_[10])[0],
                            $_[11],
                            $_[12],
                            $_[13]
                        ],
                        "tbs"=> [
                            $_[38]
                        ],
                        "taban"=> [
                            $_[37],
                            $_[32]
                        ]
                    );
                    
                    array_push($p_datas, $n_data);
                    #echo json_encode($p_datas);
                }
                usort($p_datas, function($a, $b){
               
                    return strcmp($b['tbs'][0], $a['tbs'][0]);
                });
                return $p_datas;
            }
        }
    }

    try{
        if($_SERVER['REQUEST_METHOD'] == "GET"){
        
            $ust_puan = $_GET['ust_puan'] == "" ? ($_GET['alt_puan'] == "" ? 150 : $_GET['alt_puan']) + 100 : $_GET['ust_puan'];
            
            
            function tr_toUpper($veri) {
                return strtoupper (str_replace(array ('ı', 'i', 'ğ', 'ü', 'ş', 'ö', 'ç' ),array ('I', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç' ),$veri));
            }
            $yokatlas_vars = array();
    
            if($_GET['sehir'] != "") {
                $desiredSehir = explode(",", $_GET['sehir']);
          
                if( is_array($desiredSehir) && count($desiredSehir) > 0 ){
                    for ($i=0; $i < count($desiredSehir); $i++) {
                        
                        $yokatlas = new YOKATLAS(array(
                            'puan_turu' => $_GET['pturu'] == "" ? "tyt" : $_GET['pturu'],
                            'ust_puan' => $ust_puan > 500 ? 500 : $ust_puan,
                            'alt_puan' => $_GET['alt_puan'] == "" ? 150 : $_GET['alt_puan'],
                            'ust_bs' => $_GET['ust_bs'] == "" ? 0 : $_GET['ust_bs'],
                            'alt_bs' => $_GET['alt_bs'] == "" ? 3000000 : $_GET['alt_bs'],
                            'universite_turu' => $_GET['uni_turu'] == "hepsi" ? "" : $_GET['uni_turu'],
                            'ogretim_turu' => $_GET['ogretim_turu'] == "hepsi" ? "" : $_GET['ogretim_turu'],
                            'program_adi' => $_GET['bolum'] == "hepsi" ? "" : $_GET['bolum'],
                            'sehir_adi' => $desiredSehir[$i]
                        ));
                        $yokatlas_data = $yokatlas->parse();
                        $yokatlas_vars = array_merge($yokatlas_vars, $yokatlas_data);
                    }
                    /*
                    $yokatlas_data = array_values(array_filter($yokatlas_data, 
                    function ($v){
                        $desiredSehir = explode(",", $_GET['sehir']);
                        for ($i=0; $i < count($desiredSehir); $i++) { 
                            
                            
                            if(strcmp(iconv('ASCII', 'UTF-8//IGNORE', mb_convert_case($v['sehir_adi'], MB_CASE_LOWER, "utf-8")), mb_convert_case(trim($desiredSehir[$i]), MB_CASE_LOWER, "ISO-8859-9")) == 0){
                                return true;
                            }
                        }
                    }));
                    */
                }
            }else{
                $yokatlas = new YOKATLAS(array(
                    'puan_turu' => $_GET['pturu'] == "" ? "tyt" : $_GET['pturu'],
                    'ust_puan' => $ust_puan > 500 ? 500 : $ust_puan,
                    'alt_puan' => $_GET['alt_puan'] == "" ? 150 : $_GET['alt_puan'],
                    'ust_bs' => $_GET['ust_bs'] == "" ? 0 : $_GET['ust_bs'],
                    'alt_bs' => $_GET['alt_bs'] == "" ? 3000000 : $_GET['alt_bs'],
                    'universite_turu' => $_GET['uni_turu'] == "hepsi" ? "" : $_GET['uni_turu'],
                    'ogretim_turu' => $_GET['ogretim_turu'] == "hepsi" ? "" : $_GET['ogretim_turu'],
                    'program_adi' => $_GET['bolum'] == "hepsi" ? "" : $_GET['bolum']
                ));
                
                $yokatlas_data = $yokatlas->parse();
                
                
                $yokatlas_vars = array_merge($yokatlas_vars, $yokatlas_data);
            }
    
            
    
    
            if($_GET['universite'] != "") {
                $desireduniversite = explode(",", $_GET['universite']);
          
                if( is_array($desireduniversite) && count($desireduniversite) > 0 ){
                              
                    $yokatlas_vars = array_values(array_filter($yokatlas_vars, 
                    function ($v){
                        $desireduniversite = explode(",", $_GET['universite']);
                        for ($i=0; $i < count($desireduniversite); $i++) { 
                            
                            if(strcmp(tr_toUpper($v['universite']), tr_toUpper($desireduniversite[$i])) == 0){
                                return true;
                            }
                        }
                    }));
                    
                }
            }
    
            if($_GET['egitim_dili'] != "") {
                $desiredegitim_dili = explode(",", $_GET['egitim_dili']);
          
                if( is_array($desiredegitim_dili) && count($desiredegitim_dili) > 0 ){
                              
                    $yokatlas_vars = array_values(array_filter($yokatlas_vars, 
                    function ($v){
                        $desiredegitim_dili = explode(",", $_GET['egitim_dili']);
                        for ($i=0; $i < count($desiredegitim_dili); $i++) { 
                            
                            if(strcmp(tr_toUpper($v['ogretim_dili']), tr_toUpper($desiredegitim_dili[$i])) == 0){
                                return true;
                            }
                        }
                    }));
                    
                }
            }
    
            $u_array = array();
            foreach ($yokatlas_vars as $key => $value){
                if(!in_array($value, $u_array))
                  $u_array[$key]=$value;
            }
    
            echo json_encode($yokatlas_vars);
        }   
    }catch(Exception $e){
        echo $e;
    }
?>
