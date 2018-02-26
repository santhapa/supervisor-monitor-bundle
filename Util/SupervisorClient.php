<?php 

namespace ZO\Bundle\SupervisorMonitorBundle\Util;
use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;

class SupervisorClient{
	private $servers = array();

	public function __construct($servers){
		$this->servers = $servers;
	}
	public function getServers(){
		return $this->servers;
	}

	public function getServersListVersion(){
		if(empty($this->servers)) return;

		foreach($this->servers as $name=>$config){
			$client = $this->createClient($config['host'], $config['port'], $config['username'], $config['password']);
			$list[$name] = $this->sendRequest($client, 'getAllProcessInfo');
			$version[$name] = $this->sendRequest($client, 'getSupervisorVersion');
		}

		return array(
			'version' => $version,
			'services' => $list,
		);
	}

	public function startAllService($server){
		$serverConfig = array_key_exists($server, $this->servers) ? $this->servers[$server]: null;
		if(!$serverConfig) return;

		$client = $this->createServerClient($serverConfig);
		$res = $this->sendRequest($client, 'startAllProcesses',array(new Value(1)));
		if(isset($res->errno) && $res->errno == 0){
			return $res;
		}else{
			return false;
		}
	}

	public function stopAllService($server){
		$serverConfig = array_key_exists($server, $this->servers) ? $this->servers[$server]: null;
		if(!$serverConfig) return;

		$client = $this->createServerClient($serverConfig);
		$res = $this->sendRequest($client, 'stopAllProcesses',array(new Value(1)));
		if(isset($res->errno) && $res->errno == 0){
			return $res;
		}else{
			return false;
		}
	}

	public function restartAllService($server){
		$serverConfig = array_key_exists($server, $this->servers) ? $this->servers[$server]: null;
		if(!$serverConfig) return;

		$client = $this->createServerClient($serverConfig);
		$this->sendRequest($client, 'stopAllProcesses',array(new Value(1)));
		sleep(2);
		$res = $this->sendRequest($client, 'startAllProcesses',array(new Value(1)));
		if(isset($res->errno) && $res->errno == 0){
			return $res;
		}else{
			return false;
		}
	}

	public function startService($server, $worker){
		$serverConfig = array_key_exists($server, $this->servers) ? $this->servers[$server]: null;
		if(!$serverConfig) return;

		$client = $this->createServerClient($serverConfig);
		$res = $this->sendRequest($client, 'startProcess',array(new Value($worker)));
		if(isset($res->errno) && $res->errno == 0){
			return $res;
		}else{
			return false;
		}
	}

	public function stopService($server, $worker){
		$serverConfig = array_key_exists($server, $this->servers) ? $this->servers[$server]: null;
		if(!$serverConfig) return;

		$client = $this->createServerClient($serverConfig);
		$res = $this->sendRequest($client, 'stopProcess',array(new Value($worker)));
		if(isset($res->errno) && $res->errno == 0){
			return $res;
		}else{
			return false;
		}
	}

	public function restartService($server, $worker){
		$serverConfig = array_key_exists($server, $this->servers) ? $this->servers[$server]: null;
		if(!$serverConfig) return;

		$client = $this->createServerClient($serverConfig);
		$this->sendRequest($client, 'stopProcess',array(new Value($worker)));
		sleep(2);
		$res = $this->sendRequest($client, 'startProcess',array(new Value($worker)));
		if(isset($res->errno) && $res->errno == 0){
			return $res;
		}else{
			return false;
		}
	}

	public function clearServiceLog($server, $worker){
		$serverConfig = array_key_exists($server, $this->servers) ? $this->servers[$server]: null;
		if(!$serverConfig) return;

		$client = $this->createServerClient($serverConfig);
		$res = $this->sendRequest($client, 'clearProcessLogs',array(new Value($worker)));
		if(isset($res->errno) && $res->errno == 0){
			return $res;
		}else{
			return false;
		}
	}


	private function createServerClient($serverConfig){
		return $this->createClient($serverConfig['host'], $serverConfig['port'], $serverConfig['username'], $serverConfig['password']);
	}

	private function createClient($host, $port = null, $username = null, $password = null){
		$path = $host;
		if($port){
			$path.=':'.$port;
		}
		$path.='/RPC2';

		$client = new Client($path);
		if($username || $password){
			$client->setCredentials($username, $password);
		}

		return $client;
	}

	private function sendRequest($client, $method, $params = array()){
		return $client->send(new Request('supervisor.'.$method, $params));
	}
}