<?php

namespace App\Models\Spider;

use App\Models\Category;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Message;

class CnvSpider
{
    private $client;
    private $jar;
    private $validtoken;
    private $categories;

    public function __construct()
    {
        $this->client = new Client(['cookies' => true]);
        $this->jar = new CookieJar();
        $this->categories = Category::getCategories();
    }

    public function login($email, $password)
    {
        $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Account/Login', [
            'cookies' => $this->jar
        ]);
        
        $body = $response->getBody();

        $this->parseRequestToken($body);

        try {
            $response = $this->client->request('POST', 'https://www.maoch.cn/CnvProgram/Account/Login', [
                'form_params' => [
                    'Email'=>$email,
                    'Password'=>$password,
                    'RememberMe'=>'false',
                    '__RequestVerificationToken'=>$this->validtoken
                ],
                'cookies'=>$this->jar,
                'http_errors'=>true
            ]);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            //echo \GuzzleHttp\Psr7\Message::toString($e->getRequest());
            //echo \GuzzleHttp\Psr7\Message::toString($e->getResponse());

            return false;
        }

        $this->validtoken = '';

        return true;
    }

    public function getPrograms($page=1, $lines=25)
    {
        if($this->validtoken == '') {          
            $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Programs', [
                'cookies'=>$this->jar
            ]);
        }
        else {
            $response = $this->client->request('POST', 'https://www.maoch.cn/CnvProgram/Programs', [
                'cookies'=>$this->jar,
                'form_params' => [
                    'Page'=>$page,
                    'Lines'=>$lines,
                    'Category'=>'全部分类',
                    '__RequestVerificationToken'=>$this->validtoken
                ]
            ]);
        }
        
        $body = $response->getBody();
        $this->parseRequestToken($body);
        $items = $this->parsePrograms($body);
        return $items;
    }

    public function getMeterials($page=1, $lines=25)
    {
        if($this->validtoken == '') {          
            $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Meterials', [
                'cookies'=>$this->jar
            ]);
        }
        else {
            $response = $this->client->request('POST', 'https://www.maoch.cn/CnvProgram/Meterials', [
                'cookies'=>$this->jar,
                'form_params' => [
                    'Page'=>$page,
                    'Lines'=>$lines,
                    'Category'=>'全部分类',
                    '__RequestVerificationToken'=>$this->validtoken
                ]
            ]);
        }
        
        $body = $response->getBody();
        $this->parseRequestToken($body);
        $items = $this->parseMeterials($body);
        return $items;
    }

    private function parsePrograms($body)
    {
        $body = str_ireplace(['<span>','</span>'], '', $body);
        $s = strpos($body, '<table class="table">');
        $e = strpos($body, '</table>') + 8;
        $table = substr($body, $s, $e-$s);
        
        $xml = simplexml_load_string($table);

        $v = $xml->xpath("/table/tr/td");

        $items = [];
        $total = count($v);
        for($i=0;$i<$total;$i+=11) {

            $items[] = [
                'name' => trim((string)$v[$i+3]),
                'comment' => trim((string)$v[$i+4]),
                'unique_no' => trim((string)$v[$i+1]),
                'artist' => trim((string)$v[$i+2]),
                'album' => trim((string)$v[$i+5]),
                'duration' => trim((string)$v[$i+6]),
                'company' => trim((string)$v[$i+7]),
                'co_artist' => trim((string)$v[$i+8]),
                'product_date' => trim((string)$v[$i+9]),
                'air_date' => trim((string)$v[$i+10]),
            ];
            
        }

        return $items;
    }

    /*
<td style="min-width:100px">
                        VCNM23000087
                    </td>
                    <td style="min-width:60px">
                        刘若英 毛不易 
                    </td>
                    <td style="min-width:150px">
                        消愁
                    </td>
                    <td style="min-width:150px">
                        现场演出画面
                    </td>
                    <td style="width:100px">
                         
                    </td>
                    <td style="min-width:100px">
                        00:04:10:00
                    </td>
                    <td style="min-width:80px">
                        相信音乐 
                    </td>
                    <td style="width:150px">
                        <span>毛不易</span>
                    </td>
                    <td style="min-width:100px">
                        
                    </td>
                    <td style="min-width:80px">
                        
                    </td>
    */

    private function parseMeterials($body)
    {
        $m = preg_match_all('/<td\sstyle=\"width:120px\">[\s]+(.*)[\s]+<\/td>[\s]+<td\sstyle=\"width:150px\">[\s]+(.*)[\s]+<\/td>[\s]+<td\sstyle=\"width:110px\">[\s]+(.*)[\s]+<\/td>[\s]+<td\sstyle=\"width:130px\">[\s]+(.*)[\s]+<\/td>[\s]+<td\sstyle=\"width:90px\">[\s]+(.*)[\s]+<\/td>[\s]+<td>[\s]+(\d+)[\s]+<\/td>[\s]+/',$body, $matches);
        if(!$m) return false;
        $name = $matches[1];
        $comment = $matches[2];
        $category = $matches[3];
        $no = $matches[4];
        $duration = $matches[5];
        $frames = $matches[6];
        $items = [];
        foreach($name as $idx=>$v) {
            $items[] = [
                'name' => $v,
                'comment' => $comment[$idx],
                'unique_no' => $no[$idx],
                'category' => array_search(trim($category[$idx]), $this->categories),
                'duration' => $duration[$idx],
                'frames' => $frames[$idx]
            ];
        }

        return $items;
    }

    private function parseRequestToken($body)
    {
        $m = preg_match('/value=\"([\w\-]+)\" \/>/', $body, $match);

        if($m) $this->validtoken = $match[1];
    }
}