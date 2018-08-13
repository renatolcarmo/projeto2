<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Http\Client\Adapter\Curl;

use RB\Sphinx\Hmac\HMAC;
use RB\Sphinx\Hmac\Algorithm\HMACv0;
use RB\Sphinx\Hmac\Hash\PHPHash;
use RB\Sphinx\Hmac\Key\StaticKey;
use RB\Sphinx\Hmac\Nonce\DummyNonce;
use RB\Sphinx\Hmac\Zend\Client\HMACHttpClient;

class IndexController extends AbstractActionController
{
    protected $url = 'http://localhost/apigility/public/certificado';

    public function indexAction()
    {

        $list = json_decode($this->getWS($this->url), true);

        return new ViewModel(array('certificado' => $list));
  
    }

    public function addAction()
    {

        $id = $this->params()->fromRoute("id", 0);

        $request = $this->getRequest();
        if($request->isPost())
        {
            $files =  $request->getFiles()->toArray();

            $nome = $request->getPost("nome");
            $certificado = file_get_contents($files['certificado']['tmp_name']);
            
            $data = array( 
                'nome' =>  $nome,
                'certificado' => $certificado
            );

            $rs = $this->postWS($this->url, $data);
            //$rs = $this->testHmac($this->url, $data);

            return $this->redirect()->toRoute('certificate');
        }
        return new ViewModel(array('id' => $id));

    }

    public function delAction()
    {
        $id = $this->params()->fromRoute("id", 0);
        $url = $this->url . '/' . $id;
        $list = json_decode($this->getWS($url), true);

        $request = $this->getRequest();
        if($request->isPost())
        {

            $rs = $this->deleteWS($url, $list);

            return $this->redirect()->toRoute('certificate');
        }
        return new ViewModel($list);
    }

    public function detailAction()
    {

        $id = $this->params()->fromRoute("id", 0);
        $url = $this->url . '/' . $id;
      
        $list = json_decode($this->getWS($url), true);
        return new ViewModel($list);

    }

    public function getWS($urlWS)
    {
        $client = new Client($urlWS);
        $client->setMethod('GET');
        $client->setHeaders([
            'Accept' => 'application/json',
        ]);
        $response = $client->send();
        $body = $response->getBody(); 
        return $body;
    }

    public function postWS($urlWS, $data)
    {          
        $data_string = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $urlWS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))                                                                       
        );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function deleteWS($urlWS, $data)
    {
        $data_string = json_encode($data);                                                      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $urlWS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))                                                                       
        );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function testHmac($urlWS, $data){
 
        // Fiz alguns testes com a biblioteca RB\Sphinx\Hmac
        // Mas ainda não obtive sucesso
        $data_string = json_encode($data); 

        $hmac = new HMAC( new HMACv0(), new PHPHash('sha1'), new StaticKey( 'SEGREDO123' ), new DummyNonce () );
        $hmac->setKeyId ( 'APPid123' );
        $hmac->setNonceValue('meuNonce');
        
        $client = new HMACHttpClient( $urlWS );
        $client->setMethod('POST');
        $client->setHmac($hmac);
        $client->setRawBody($data_string);
        $client->setHeaders([
            'Accept' => 'application/json',
        ]);
        
        try {
            $client->send();
        } catch (Exception $e) {
            echo "##### ERRO #####", PHP_EOL;
            echo $e->getCode(), ' : ', $e->getMessage(), PHP_EOL;
            echo "##### ERRO #####", PHP_EOL, PHP_EOL;
        }
        $response = $client->getResponse();
        echo "Response Status Code: ", $response->getStatusCode(), PHP_EOL, PHP_EOL;
        echo "Response Headers: ";
        print_r( $response->getHeaders()->toArray() );
        echo PHP_EOL;
        echo "Response Body:", PHP_EOL;
        echo $response->getBody();
        echo PHP_EOL, PHP_EOL;        
        exit;
    }

}
