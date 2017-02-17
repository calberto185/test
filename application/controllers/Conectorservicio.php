<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conectorservicio extends CI_Controller {

	private $_url;
	private $_token;
	private $_path;
	private $keyID;

	function __construct()
	{
		parent::__construct();
		$this->_url = "https://sencillo-fb7f8.firebaseio.com";
		$this->_token = "fQvL7EEFzib09uOh4uOqwj5BuqfFsiJ7vSoPc4Ow";
		$this->_pathcuenta = "/cuentasdev";
		$this->_pathdetalle = "/detallecuentasdev";
		$this->_pathpagos = "/pagosdev";
		$this->load->helper('date');
	}

	public function index()
	{
		$this->load->view('main');
	}

	private function isJson($string) {
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	private function validaridcuenta($id){
		$firebase = new \Firebase\FirebaseLib($this->_url, $this->_token);

		$return = $firebase->get($this->_pathcuenta.'/'.$id);

		if($return!='null'){
			//si se obtuvo valor
			return true;
		}else{
			//no existe clave
			return false;
		}
		return false;
	}

	public function wsverificarpagoidcuenta(){
		$json = new  Services_JSON();
		$valor = $this->input->raw_input_stream;
		
		$respuesta = array();

		if($this->isJson($valor)){

			$decodificado = $json->decode($valor);

			//json decodificado correctamente
			//primero validamos el user y key
			if($decodificado->user == 'sencillo@restaurant.pe' && $decodificado->password == 'Xyj8XBXsMf2dugs' ){
				//credenciales validas

				$keyID = $decodificado->idcuenta;
				
				if($this->validaridcuenta($keyID)){

					$firebase = new \Firebase\FirebaseLib($this->_url, $this->_token);

					$return = $firebase->get($this->_pathpagos, array('orderBy' => '"idcuenta"','equalTo' =>'"'.$keyID.'"'));

					//decodificamos $return
					$decodificadoreturn = $json->decode($return);

					$keyPago = "";
					$fechaPago = "";
					$montoPago = "";
					$numoperacion = "";

					foreach ($decodificadoreturn as $key => $value) {
						$keyPago = $key;
						$fechaPago = $value->creado;
						$montoPago = $value->monto;
						$numoperacion = $value->numoperacion;
					}

					if($keyPago!='null' && strlen($keyPago)>0 ){
						//si se obtuvo valor
						$respuesta =  array('codigo' => 200,'mensaje'=> 'Cuenta ya se encuentra pagada [IDCUENTA PAGADA]','idpago'=>$keyPago, 'idcuenta'=>$keyID, 'fechapago'=>$fechaPago, 'monto'=>$montoPago, 'estado'=>'PR', 'numoperacion'=>$numoperacion);
					}else{
						//no existe clave
						$respuesta =  array('codigo' => 210,'mensaje'=> 'IdCuenta pendiente de pago','idpago'=>'-', 'idcuenta'=>$keyID, 'fechapago'=>'-', 'monto'=>'0', 'estado'=>'PP', 'numoperacion'=>'-');
					}
					
				}else{
					//cuenta no existe
					$respuesta =  array('codigo' => 210,'mensaje'=> 'IdCuenta inexistente en base de datos','idpago'=>'-' );
				}

			}else{
				//credenciales invalidas
				$respuesta =  array('codigo' => 150,'mensaje'=> 'Credenciales invalidas','idpago'=>'-' );
			}
		}else{
			//error al decodificar json
			$respuesta =  array('codigo' => 110,'mensaje'=> 'Cadena JSON con incoherencias, no se puede decodificar','idpago'=>'-' );
		}

		$codificado = $json->encode($respuesta);
		print $codificado;
		//print $return;
	}

	public function wsverificaridcuenta(){
		$json = new  Services_JSON();
		$valor = $this->input->raw_input_stream;
		
		$respuesta = array();

		if($this->isJson($valor)){

			$decodificado = $json->decode($valor);

			//json decodificado correctamente
			//primero validamos el user y key
			if($decodificado->user == 'sencillo@restaurant.pe' && $decodificado->password == 'Xyj8XBXsMf2dugs' ){
				//credenciales validas
				$firebase = new \Firebase\FirebaseLib($this->_url, $this->_token);

				$keyID = $decodificado->idcuenta;

				$return = $firebase->get($this->_pathcuenta.'/'.$keyID);

				if($return!='null'){
					//si se obtuvo valor
					$respuesta =  array('codigo' => 200,'mensaje'=> 'Se realizo operacion con exito [IDCUENTA EXISTENTE]','id'=>$keyID );
				}else{
					//no existe clave
					$respuesta =  array('codigo' => 210,'mensaje'=> 'IdCuenta inexistente en base de datos','id'=>'-' );
				}
			}else{
				//credenciales invalidas
				$respuesta =  array('codigo' => 150,'mensaje'=> 'Credenciales invalidas','id'=>'-' );
			}
		}else{
			//error al decodificar json
			$respuesta =  array('codigo' => 110,'mensaje'=> 'Cadena JSON con incoherencias, no se puede decodificar','id'=>'-' );
		}

		$codificado = $json->encode($respuesta);
		print $codificado;
	}

	public function wsactualizarcuentadev(){
		$json = new  Services_JSON();
		$valor = $this->input->raw_input_stream;
		
		$respuesta = array();

		if($this->isJson($valor)){

			$decodificado = $json->decode($valor);

			//json decodificado correctamente
			//primero validamos el user y key
			if($decodificado->user == 'sencillo@restaurant.pe' && $decodificado->password == 'Xyj8XBXsMf2dugs' ){

				//credenciales validas
				$cuenta = $decodificado->response->cuenta;
				$detalle = $decodificado->response->detalle;

				$firebase = new \Firebase\FirebaseLib($this->_url, $this->_token);

				$keyID = $decodificado->idcuenta;

				//validamos si el idcuenta existe

				if($this->validaridcuenta($keyID)){

					$cuenta->procedencia = 'EXT'; //es externo
					$cuenta->tipopago = 'PD'; //tipo de pago directo, sin distribucion

					//inclusion de la fecha de registro
					//$time = time();
					//$cuenta->fecharegistro = date("d-m-Y H:i", $time);
					$cuenta->fecharegistro = $this->fechaHora_actual("UM6");

					//cuenta existente
					$return = $firebase->set($this->_pathcuenta.'/'.$keyID, $cuenta);

					if($return!=null){

						$return = $firebase->set($this->_pathdetalle.'/'.$keyID, $detalle);

						if($return!=null){
							$respuesta =  array('codigo' => 200,'mensaje'=> 'Se realizo operacion con exito','id'=>$keyID );
						}else{
							$respuesta =  array('codigo' => 250,'mensaje'=> 'No se puede conecta con servicio Sencilloapp','id'=>'-' );
						}
					}else{
						$respuesta =  array('codigo' => 250,'mensaje'=> 'No se puede conecta con servicio Sencilloapp','id'=>'-' );
					}
				}else{
					//cuenta no existe
					$respuesta =  array('codigo' => 210,'mensaje'=> 'IdCuenta inexistente en base de datos','id'=>'-' );
				}
			}else{
				//credenciales invalidas
				$respuesta =  array('codigo' => 150,'mensaje'=> 'Credenciales invalidas','id'=>'-' );
			}
		}else{
			//error al decodificar json
			$respuesta =  array('codigo' => 110,'mensaje'=> 'Cadena JSON con incoherencias, no se puede decodificar','id'=>'-' );
		}

		$codificado = $json->encode($respuesta);
		print $codificado;
	}

	public function wsinsertarcuentadev(){
		$json = new  Services_JSON();
		$valor = $this->input->raw_input_stream;
		
		$respuesta = array();

		if($this->isJson($valor)){

			$decodificado = $json->decode($valor);

			//json decodificado correctamente
			//primero validamos el user y key
			if($decodificado->user == 'sencillo@restaurant.pe' && $decodificado->password == 'Xyj8XBXsMf2dugs' ){
				//credenciales validas
				$cuenta = $decodificado->response->cuenta;
				$detalle = $decodificado->response->detalle;

				$firebase = new \Firebase\FirebaseLib($this->_url, $this->_token);

				$cuenta->procedencia = 'EXT'; //es externo
				$cuenta->tipopago = 'PD'; //tipo de pago directo, sin distribucion

				//inclusion de la fecha de registro
				//$time = time();
				//$cuenta->fecharegistro = date("d-m-Y H:i", $time);
				$cuenta->fecharegistro = $this->fechaHora_actual("UM6");

				$return = $firebase->push($this->_pathcuenta, $cuenta);

				if($return){

					$returndecodificado = $json->decode($return);
					$keyID = $returndecodificado->name;

					$return = $firebase->set($this->_pathdetalle.'/'.$keyID, $detalle);

					if($return){
						$respuesta =  array('codigo' => 200,'mensaje'=> 'Se realizo operacion con exito','id'=>$keyID );
					}else{
						$respuesta =  array('codigo' => 250,'mensaje'=> 'No se puede conecta con servicio Sencilloapp','id'=>'-' );
					}
				}else{
					$respuesta =  array('codigo' => 250,'mensaje'=> 'No se puede conecta con servicio Sencilloapp','id'=>'-' );
				}

			}else{
				//credenciales invalidas
				$respuesta =  array('codigo' => 150,'mensaje'=> 'Credenciales invalidas','id'=>'-' );
			}
		}else{
			//error al decodificar json
			$respuesta =  array('codigo' => 110,'mensaje'=> 'Cadena JSON con incoherencias, no se puede decodificar','id'=>'-' );
		}

		$codificado = $json->encode($respuesta);
		print $codificado;

	}

	private function fechaHora_actual($zonaHoraria)
	{ 
		$esVerano = date('I', now()); //Obtenemos TRUE si es horario de verano
		$fechaGMTUnix = now(); //Fecha actual en GMT
		$fechaLocalUnix = gmt_to_local($fechaGMTUnix, $zonaHoraria, $esVerano); //Convertimos la fecha GMT a local a partir del código de zona horaria
		 
		$fechaLocalFormateada = mdate("%d/%m/%Y %H:%i", $fechaLocalUnix); //Formato español (dd/mm/yyyy HH:mm:ss)
		 
		return $fechaLocalFormateada;
	}

}
?>