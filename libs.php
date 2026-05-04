<?php
    function html_parse($chid,$next){
        $html = curl_auto($next == 0 ? $chid : $chid.'?before='.$next);
        if (stripos($html,'</header>')===false || stripos($html,'</main>')===false){
            http_response_code(400);die;
        }
        // disable telegram internal pics
        $html = str_ireplace("background-image:url('//","background-temp:url('//",$html);
        // replace tel pics with local cache
        while (stripos($html,"background-image:url('https://")!==false){
            $pic = str_ireplace(['"',"'"],'',str_find($html,["background-image:url('https://"],')'));
            $html = str_ireplace("https://".$pic,"proxy.php?url=".bin2hex($pic),$html);
        }
        while (stripos($html,'<img src="https://')!==false){
            $pic = str_find($html,['<img src="https://'],'"');
            $html = str_ireplace("https://".$pic,"proxy.php?url=".bin2hex($pic),$html);
        }
        // fix date and time
        while (stripos($html,'<time datetime="')!==false){
            $time = str_find($html,['<time datetime="'],'"');
            $traw = str_find($html,['datetime="','*<time '],'</time>');
            $html = str_ireplace($traw,'class="time">'.date_easy_read($time),$html);
        }
        $chanpic = str_find($html,['tgme_page_photo_image','src="'],'"');
        $content = str_find($html,['</header>'],'</main>').'</main>';
        // replace next page
        if (stripos($content,'messages_more_wrap')!==false){
            $link = str_find($content,['messages_more_wrap','>'],'</div>');
            $data = str_find($content,['messages_more_wrap','data-before="'],'"');
            $content = str_ireplace($link,'<a class="tme_messages_more" href="#" onclick="load_more('.$data.',\''.$chid.'\',this)"></a>',$content);
        }
        // delete after link
        if (stripos($content,'data-after=')!==false){
            $content = str_ireplace('<a '.str_find($content,['data-after=','*<a'],'</a>').'</a>','',$content);
        }
        // set channel info
        if ($next == 0){
            $subscribers = str_find($html,['<div class="tgme_header_counter">'],'</div>');
            $channelname = str_find($html,['<div class="tgme_header_title">'],'</div>');
            $content = '<header class="tgme_header search_collapsed"><div class="menu_channel_header"><div class="menu_channel_avatar"><img src="'.$chanpic.'"></div><div class="menu_channel_info"><p>'.strip_tags($channelname).'</p><span>'.$subscribers.'</span></div></div></header>' . $content;
            setcookie('lastread_'.$chid,str_find($html,['*data-post="',"/"],'"'),time() + 30*24*60*60,"/");
        }
        echo $content;die;
    }
    function json_info($chid){
        $html = curl_auto($chid);
        $isok = stripos($html,'<meta property="og:title" content="')!==false;
        $file = __DIR__.'/.cache/'.$chid.'.json';
        if (!$isok && file_exists($file)){
            echo json_encode(array_merge(json_decode(file_get_contents($file),true),['newmsg'=>'OFF']));die;
        }else if (!$isok){
            http_response_code(400);die;
        }
        $json = ['name'=>str_find($html,['<meta property="og:title" content="'],'"'), 'desc'=>'','avatar'=>'','date'=>0,'datestr'=>'','newmsg'=>''];
        $pic  = str_find($html,['<meta property="og:image" content="https://'],'"');
        $hash = md5($pic);
        if (curl_download("https://".$pic,$hash.'.jpg')!==false){
            $json['avatar'] = '.cache/'.$hash.'.jpg';
        }
        if (stripos($html,'data-post="')!==false){
            $lastpost = str_find($html,['*data-post="'],false);
            $lastcode = intval(str_find($lastpost,['/'],'"'));
            if (stripos($lastpost,'tgme_widget_message_text')!==false){
                $json['desc'] = strip_tags(str_find($lastpost,['tgme_widget_message_text','>'],'</div>'));
            }else{
                $json['desc'] = 'فایل';
            }
            if (stripos($lastpost,'<time datetime=')!==false){
                $time = str_find($lastpost,['<time datetime="'],'"');
                $json['date'] = intval(strtotime($time));
                $json['datestr'] = substr(date_convert($time),5);
            }
            $lastread = isset($_COOKIE['lastread_'.$chid]) ? intval($_COOKIE['lastread_'.$chid]) : 0;
            if ($lastcode != $lastread){
                $json['newmsg'] = "+".(min($lastcode - $lastread, 99));
            }
        }
        $data = json_encode($json);
        file_put_contents($file,$data);
        echo $data;die;
    }
    function curl_setopts($ch,$url,$headers=[]){
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT       , 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_RESOLVE,[str_find($url,['https://'],'/').':443:216.239.38.120']);
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36';
        $headers[] = 'Pragma: no-cache';
		$headers[] = 'Cache-Control: no-cache';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    function curl_auto($params){
        $res = curl_get($params);
        for ($i=1; $i <= 2; $i++) { 
            if (stripos($res,'cURL error')!==false && stripos($res,'timeout')!==false){
                $res = curl_get($params,$i);
            }else{
                break;
            }
        }
        return $res;
    }
    function curl_get($params,$try=0){
        $dst= 'https://'.domain_pick($try > 1).'/s/'.$params.(stripos($params,'?')===false ? '?' : '&').'_x_tr_sl=el&_x_tr_tl=en&_x_tr_hl=en&_x_tr_pto=wapp';
        $ch = curl_init($dst);
        curl_setopts($ch,$dst,['Host: t-me.translate.goog']);
        if ($try > 0){
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $ciphers = ['ECDHE-ECDSA-AES128-GCM-SHA256','ECDHE-RSA-AES128-GCM-SHA256','ECDHE-ECDSA-AES256-GCM-SHA384','ECDHE-RSA-AES256-GCM-SHA384'];
            shuffle($ciphers);
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, implode(":",array_slice($ciphers,0,rand(1, count($ciphers)))));
        }
        $response = curl_exec($ch);
        if ($response === false) {
            return 'cURL error: '.curl_error($ch);
        }
        return $response;
    }
    function curl_download($url,$name){
        if (file_exists(__DIR__.'/.cache/'.$name)) return true;
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['host'])) return false;
        $host = $parsedUrl['host'];
        $dst= str_ireplace($host,'www.google.com',$url);
        $ch = curl_init($dst);
        curl_setopts($ch,$dst,['Host: '.(str_ireplace('.','-',$host)).'.translate.goog']);
        $imageData = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode != 200) return false;
        file_put_contents(__DIR__.'/.cache/'.$name, $imageData);
        return true;
    }
    function str_find($str,$find,$end){
		$start = 0;
		for ($i=0;$i<count($find);$i++)
		{
			if (substr($find[$i],0,1)=='*'){
                $find[$i] = substr($find[$i],1);
                if ($start == 0){
                    $start = strrpos($str,$find[$i]);
                }else{
                    $start = strrpos($str,$find[$i],-(strlen($str)-$start));
                }
			}else{
				$start = stripos($str,$find[$i],$start);
			}
			if ($start === false) return false;
			$start += strlen($find[$i]);
		}
		if (is_bool($end)===true){
			if ($end == false){
				return trim(substr($str,$start));
			}else{
				return trim(substr($str,0,$start));
			}
		}else{
			$fin = stripos($str,$end,$start);
			if ($fin === false) return false;
			return trim(substr($str,$start,($fin-$start)));
		}
	}
    function file_parse(){
        $channels = trim(file_get_contents(__DIR__.'/channels.txt'));
        $channels = str_ireplace(["'",'"','@'],'',$channels);
        $channels = str_ireplace(['https://','http://'],'',$channels);
        $channels = str_ireplace('/s/','',$channels);
        $channels = str_ireplace('t.me','',$channels);
        $channels = str_ireplace('/','',$channels);
        $results  = [];
        foreach (explode(PHP_EOL,$channels) as $val) {
            $name = strtolower(str_ireplace(' ','',$val));
            if ($name == '' || in_array($name,$results)) continue;
            array_push($results,$name);
        }
        return $results;
    }
    function date_easy_read($datetime){
        $date = date_convert($datetime);
        $today = substr(date_convert(date('Y-m-d H:i:s',strtotime('now'))),0,10);
        $yesterday = substr(date_convert(date('Y-m-d H:i:s',strtotime('-24 Hours'))),0,10);
        if (stripos($date,$today)!==false){
            return str_ireplace($today,'Today',$date);
        }
        if (stripos($date,$yesterday)!==false){
            return str_ireplace($yesterday,'Yesterday',$date);
        }else{
            return $date;
        }
    }
    function date_convert($datetime){
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone('Asia/Tehran'));
        $gy = (int)$dt->format('Y');
        $gm = (int)$dt->format('m');
        $gd = (int)$dt->format('d');
        $time = $dt->format('H:i');
        $g_d_m = array(0,31,59,90,120,151,181,212,243,273,304,334);
        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = (365 * $gy) + floor(($gy2 + 3) / 4) - floor(($gy2 + 99) / 100) + floor(($gy2 + 399) / 400) - 80 + $gd + $g_d_m[$gm - 1];
        $jy += 33 * floor($days / 12053);
        $days %= 12053;
        $jy += 4 * floor($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += floor(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $jm = ($days < 186) ? 1 + floor($days / 31) : 7 + floor(($days - 186) / 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
        return sprintf('%04d-%02d-%02d %s', $jy, $jm, $jd, $time);
    }
    function domain_pick($rand=true){
        if (!$rand) return 'www.google.com';
        $domains = ['safebrowsing.google.com','images.google.com','maps.google.com','news.google.com','scholar.google.com','meet.google.com','mail.google.com','drive.google.com'];
        return $domains[array_rand($domains)];
    }
