<?php
 
namespace AutoblogPro\Libs;

use AutoblogPro\Libs\Curl;
use AutoblogPro\Libs\UserAgent;
use \RoNoLo\JsonExtractor\JsonExtractorService; 

class GoogleImage
{
    public static function get($keyword)
    {
		global $config;

		$keyword = str_replace(' ', '+', strtolower($keyword));
                //$keyword = urlencode($keyword);

		$bw = '';
		if($config->black_and_white_only){
			$bw = '&tbs=ic:gray';
		}

                $uas = [
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36 Edg/88.0.705.68",
            "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:85.0) Gecko/20100101 Firefox/85.0",
            "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36 Vivaldi/3.6",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36 Vivaldi/3.6",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.2 Safari/605.1.15",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 11.2; rv:85.0) Gecko/20100101 Firefox/85.0",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36 Vivaldi/3.6",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36 Edg/88.0.705.63",
            ];
            $ua = $uas[array_rand($uas)];

		$source = [
			//"url" => "https://www.google.com/search?q=$keyword&source=lnms&tbm=isch$bw",
                        "url" => "https://www.google.com/search?q=$keyword&source=lnms&tbm=isch&tbs=",
			"useragent" => $ua,
                        "proxy" => $config->proxy];

		$source = json_encode($source);
		$html = Curl::get($source);

		$results = [];

		// untuk mengecek jika ada perubahan struktur web

		if(strpos($html, 'AF_initDataCallback') === false){
			file_put_contents(dirname(__FILE__).'/../../error/google.html', $html);
			return json_encode($results);
		}


		$jsonExtractor = new JsonExtractorService();
		$data = $jsonExtractor->extractAllJsonData($html);

		foreach($data as $index=>$dt){
			$json = json_encode($dt);
			if(strpos($json, 'https:\/\/encrypted')!==false){
				break;
			}
		}	

		$blocks = $data[$index]['data'][56][1][0][0][1][0] ?? [];
		
		if(empty($blocks)){
			$blocks = $data[$index][56][1][0][0][1][0] ?? [];
		}

        foreach ($blocks as $block) {
			$alt = $block[1][9][2003][3] ?? '';
			$image = $block[1][3][0] ?? '';
			$thumbnail = $block[1][2][0] ?? '';
			$source = $block[1][9][2003][2] ?? '';			
			
			if (empty($alt) || empty($image) || empty($thumbnail) || empty($source)){
				$alt = $block[0][1][9][2003][3] ?? '';
				$image = $block[0][1][3][0] ?? '';
				$thumbnail = $block[0][1][2][0] ?? '';
				$source = $block[0][1][9][2003][2] ?? '';
			}

			if (!empty($alt) && !empty($image) && !empty($thumbnail) && !empty($source)) {
				$results[] = ['alt' => $alt, 'image' => $image, 'thumbnail' => $thumbnail, 'source' => $source];
			}
        }

		// struktur hanya ada thumbnail dan kategori

		if(empty($results)){
			$blocks = $data[$index][0][0][1] ?? [];

			foreach($blocks as $block){
				$alt = $block[0] ?? '';
				$image = $block[1][0] ?? '';
				$thumbnail = $block[1][0] ?? '';
				$source = 'https://encrypted-tbn0.gstatic.com/' ?? '';

				if (!empty($alt) && !empty($image) && !empty($thumbnail) && !empty($source)) {
					$results[] = ['alt' => $alt, 'image' => $image, 'thumbnail' => $thumbnail, 'source' => $source];
				}
			}
		}

		// untuk mengecek jika ada perubahan struktur web
		
		if(empty($results)){
			file_put_contents(dirname(__FILE__).'/../../error/google.json', json_encode($data[$index]));
		}
		
        return json_encode($results);
    }
  
}
