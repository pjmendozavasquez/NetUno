<?php
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(0);
set_time_limit(0);
//ini_set('max_execution_time', 300); //300 segundos = 5 minutos
// Insert the NuSOAP code
require_once('../Clases/nusoap/lib/nusoap.php');
require_once('../Clases/Conexion_Off_Line.php');
require_once('../Clases/ftp_utilities.php');

// Create an instance of the server
$server = new soap_server;

// Initialize WSDL support
$server->configureWSDL('nasftthwsdl', 'urn:nasftthwsdl');

// Put the WSDL schema types in the namespace with the tns prefix
$server->wsdl->schemaTargetNamespace = 'urn:nasftthwsdl';

/******************************************************************/
/***************** WS STRUCTURE DEFINITION SECTION ****************/
/******************************************************************/

/* ****************************************************************/
/* ***************** CM DATA STRUCTURE DEFINITION *****************/
/* ****************************************************************/
// Register the data structures used by the service
$server->wsdl->addComplexType(
    'JobSERVICIO', 'complexType', 'struct', 'all', '', array(
        'usuario' => array('name' => 'usuario', 'type' => 'xsd:string'),
        'ciudad' => array('name' => 'ciudad', 'type' => 'xsd:string'),
		'accion' => array('name' => 'accion', 'type' => 'xsd:string')
    )
);

// Register the data structures used by the service
$server->wsdl->addComplexType(
		'JobLog', 'complexType', 'struct', 'all', '', array(
        'jobid' => array('name' => 'jobid', 'type' => 'xsd:long'),
		'fecha' => array('name' => 'fecha', 'type' => 'xsd:date'),
		'ciudad' => array('name' => 'ciudad', 'type' => 'xsd:string'),
		'tecnologia' => array('name' => 'ciudad', 'type' => 'xsd:string'),//Fttx, Ott, Telefonia
        'abonado' => array('name' => 'abonado', 'type' => 'xsd:string'),
        'contrato' => array('name' => 'contrato', 'type' => 'xsd:string'),
        'serial' => array('name' => 'serial', 'type' => 'xsd:string'),//Serial PON (Fttx, Telefonia)
		'username' => array('name' => 'username', 'type' => 'xsd:string'),
		'nodo' => array('name' => 'nodo', 'type' => 'xsd:string'),
		'accion' => array('name' => 'accion', 'type' => 'xsd:string'),//Corte
        'resultado' => array('name' => 'resultado', 'type' => 'xsd:string'),
    )
);


/******************************************************************/
/********************* WS METHOD REGISTRATION *********************/
/******************************************************************/

$server->register('corteMasivo', // method name
        array('job' => 'tns:JobSERVICIO', "codigo" => "xsd:string"), // input parameters
        array('return' => 'xsd:string'), // output parameters
        'urn:nasftthwsdl', // namespace
        'urn:nasftthwsdl#corteMasivo', // soapaction
        'rpc', // style
        'encoded', // use
        'Insertar un JobSERVICIO para su corte'         // documentation
);



/******************************************************************/
/********************* PHP METHOD DEFINITIONS *********************/
/******************************************************************/

//Rutina que permite ingresar un JOB en la tabla del servidor para que se ingrese 
function corteMasivo($JOB,$codigo) {
    $ciudad = $JOB['ciudad'];
	$myFile = "/tmp/ftth_ws1.log";
	$fh = fopen($myFile, 'w') or die("can't open file");
	if  (("W6xs4#nO" == $codigo) or ("d8vnQ3nP"== $codigo))
	{	
		$ciudadCorte=validar_ciudad($ciudad);
		echo "Iniciando proceso de corte: $ciudadCorte";
		echo "Sin conexion a BD...\n";
		if ($ciudadCorte!="ERROR_CUIDAD"){
			$urlCiudad=invocar_WSCortexCiudad($ciudad);
			echo "URL=$urlCiudad";
		}
		/*
		
		if (strrpos($nodo,"I")==11)
		{
			switch ($accion){
				case 'NUEVO':
					if (strpos($producto, "FiberPon") !== false){//Es corporativo
						//$vlan = 699; //VLAN usado para corporativos
						$vlan = VLANxCiudad_ftto($ciudad); //VLAN de cientes FTTO
					}else{
						//$vlan = 600; //VLAN de cientes FTTH
						$vlan = VLANxCiudad_ftth($ciudad); //VLAN de cientes FTTH
					}
					list($codigoError, $mensajeError) = SerialesONT_Client_INCLUIRONT($JOB,"wsSerialesONT.INCLUIRONT",$vlan);
					if ($codigoError!="01"){
						$descripcion="ACCION=NUEVO: WS-INTER->Error=$codigoError, Mensaje de error=$mensajeError";
						$accion="ERRORWS";
					}
					break;
				case 'ELIMINADO': 
					list($codigoError, $mensajeError) = SerialesONT_Client_ELIMINARONT($JOB,"wsSerialesONT.ELIMINARONT");
					//echo "codigoError = $codigoError";
					//echo "\n";
					if ($codigoError!="01"){
						$descripcion="ACCION=ELIMINADO: WS-INTER->Error=$codigoError, Mensaje de error=$mensajeError";
						$accion="ERRORWS";
					}
					break;
				case 'MODIFICACION':
					list($codigoError, $mensajeError) = SerialesONT_Client_ELIMINARONT($JOB,"wsSerialesONT.ELIMINARONT");
					if (strpos($producto, "FiberPon") !== false){//Es corporativo
						//$vlan = 699; //VLAN usado para corporativos
						$vlan = VLANxCiudad_ftto($ciudad); //VLAN de cientes FTTO
					}else{
						//$vlan = 600; //VLAN de cientes FTTH
						$vlan = VLANxCiudad_ftth($ciudad); //VLAN de cientes FTTH
					}
					list($codigoError, $mensajeError) = SerialesONT_Client_INCLUIRONT($JOB,"wsSerialesONT.INCLUIRONT",$vlan);
					if ($codigoError!="01"){
						$descripcion="ACCION=MODIFICACION: WS-INTER->Error=$codigoError, Mensaje de error=$mensajeError";
						$accion="ERRORWS";
					}
					break;
				case 'CORTADO':
					list($codigoError, $mensajeError) = SerialesONT_Client_ELIMINARONT($JOB,"wsSerialesONT.ELIMINARONT");
					if ($codigoError!="01"){
						$descripcion="ACCION=CORTADO-ELIMINADO: WS-INTER->Error=$codigoError, Mensaje de error=$mensajeError";
						$accion="ERRORWS";
					}
					break;
				default:
                    throw new exception("Accion no definida.", 0);
                    break;
			}
			
		}
		$conexion = new Conexion_Off_Line();
		$conexion->_get_link('localhost', 'root', 'ploj#tut','nas2');
		//$conexion->_select_db('nas2');

		//Seleccionar los datos de la BD de la cuidad
		$query = "select * from nas_ws_ciudad_db where ws_ciudad_vc = '$ciudad' and ws_tipo_vc = 'DUMS';";
		$resultset = $conexion->_execute_resultset_bd('nas', $query);

		//se valida que traiga datos
		if ($resultset->numero_filas != 0) {

				//Se asignan los valores
				$ip = $resultset->arreglo_valor_columnas[0]['ws_ip_vc'];
				//$ip='172.16.7.50';
				$login = $resultset->arreglo_valor_columnas[0]['ws_db_login_vc'];
				//$login ='nas_ws';
				$pass = $resultset->arreglo_valor_columnas[0]['ws_db_password_vc'];
				//$pass = 'n3tun0dums';
				//$bd = $resultset->arreglo_valor_columnas[0]['ws_db_vc'];
				if ($ciudad != 355) {$bd = "ftth_server";} else {$bd="ftth_server2";}
				$conexion2 = new Conexion_Off_Line();
				$conexion2->_get_link("$ip", "$login", "$pass", "$bd");
				//$conexion2->_select_db("$bd");
						
				$query = "INSERT INTO job VALUES(";
				$query .= "NULL,'$contrato', '$abonado','$serial','$nodo','$producto','$usuario','$accion',current_date(),current_time(),'$descripcion','$user','$mac')";
				$code = $conexion2->_execute_db_query("$bd", $query);
				fwrite($fh, $query);
				$query = "SELECT LAST_INSERT_ID() as JOBID";
				$rs = $conexion2->_execute_resultset_bd("$bd", $query);
				$id = $rs->arreglo_valor_columnas[0]['JOBID'];
				fclose($fh);
				return $id;
			} else {
				return "ERROR_CUIDAD";
			}*/
		}
	else
	{
		return "ERROR_UNAUTHORIZED";
	}	
}

  function validar_ciudad($ciudad){
 		switch ($ciudad) {
				case 1: 
					$mensaje = "Caracas\n";
					break;
				case 20: 
					$mensaje = "Laboratorio\n";
					break;	
				case 39: 
					$mensaje = "Maracay\n";
                    break;
				case 43: 
					$mensaje = "Palo Negro\n";
                    break;
				case 41: 
					$mensaje = "La Victoria\n";
                    break;
				case 47: 
					$mensaje = "Turmero\n";
                    break;
				case 91: 
					$mensaje = "Valencia\n";
					break;	
				case 147: 
					$mensaje = "Barquisimeto\n";
					break;	
				case 165: 
					$mensaje = "Merida\n";
					break;	
				case 197: 
					$mensaje = "Guarenas-Guatire\n";
					break;	
				case 274: 
					$mensaje = "San Cristobal\n";
					break;	
				case 327: 
					$mensaje = "Maracaibo\n";
					break;	
				default: 
					$mensaje = "ERROR_CUIDAD";
					break;				
		}
		return $mensaje;
  }

  function invocar_WSCortexCiudad($ciudad){
 		switch ($ciudad) {
				case 1: 
					$url ="http://172.16.4.25/ccs_ftth_ws.wsdl";
					break;
				case 20: 
					$url ="http://172.16.6.131/lab_ftth_ws.wsdl";
					break;	
				case 39: 
					$url ="http://172.16.7.50/mcy_ftth_ws.wsdl";
                    break;
				case 43: 
					$url ="http://172.16.7.50/mcy_ftth_ws.wsdl";
                    break;
				case 41: 
					$url ="http://172.16.7.50/mcy_ftth_ws.wsdl";
                    break;
				case 47: 
					$url ="http://172.16.7.50/mcy_ftth_ws.wsdl";
                    break;
				case 91: 
					$url ="http://172.16.16.36/val_ftth_ws.wsdl";
					break;	
				case 147: 
					$url ="http://172.16.118.131/bqto_ftth_ws.wsdl";
					break;	
				case 165: 
					$url ="http://172.32.7.4/mda_ftth_ws.wsdl";
					break;	
				case 197: 
					$url ="http://172.16.119.8/gg_ftth_ws.wsdl";
					break;	
				case 274: 
					$url ="http://172.16.40.6/scr_ftth_ws.wsdl";
					break;	
				case 327: 
					$url ="http://172.30.31.17/mbo_ftth_ws.wsdl";
					break;	
				default: 
					break;				
		}
		return $url;
  }

 class nui {
		var $wsdl_url_session;      //WS de sesion
		var $wsdl_url_customer;     //WS de cliente
		var $wsdl_url_account;      //WS de cuenta
		var $logged;                // Si se logeo o no
		var $logged_type;           // USER o CLIENT
		var $user = "";             // Usuario que se logea
		var $pass = "";             // Password Usuario que se logea
		var $customer_check;        // Arreglo con informacion del cliente seleccionado
		var $customer_info;         // Arreglo con informacion del cliente seleccionado
		var $account_info;         // Arreglo con informacion del cliente seleccionado
		var $update_service_features;
		
		function nui($login, $pass, $portaone) {
			$this->user = $login;
			$this->pass = $pass;

			try {
				$result = $this->acceder_porta($portaone);

				if ($result) {//login correcto de un USER
					$this->logged = true;
					$this->logged_type = 'USER';
					
				} else {//verificar si el login lo hizo un cliente
					$this->user = $login;
					$this->pass = $pass;
					
					$result_client = $this->acceder_porta($portaone);

					if ($result_client) {//validar cliente que se logea                  
						$client = $this->check_customer_login(null, $user, $pass);
						
						if ($client) {//login correcto de cliente
							$this->logged = true;
							$this->logged_type = 'CLIENT';
						
						} else {//Cliente errado, user errado
							$this->logged = false;
							$this->logged_type = 'NONE1';
						}
					} else {//Problemas con usuario 
						$this->logged = false;
						$this->logged_type = 'NONE2';
					}
				}
			} catch (SoapFault $exception) { }
		}

	  /* Metodo para hacer login en PORTA a traves de los WS */
/*	  function acceder_porta($portaone) {        
			$this->wsdl_url_session = "https://mybilling.your--domain.com/wsdl/SessionAdminService.wsdl";
			$this->wsdl_url_customer = "https://mybilling.your--domain.com/wsdl/CustomerAdminService.wsdl";
			$this->wsdl_url_account = "https://mybilling.your--domain.com/wsdl/AccountAdminService.wsdl";
			try {
				$session_client = new SoapClient($this->wsdl_url_session);
				$session_response = $session_client->login(array( 'login' => $this->user, 'password' => $this->pass ));
				$session_id = $session_response->session_id;
				
				$this->auth_info = new SoapHeader(
								"http://schemas.portaone.com/soap",
								"auth_info",
								new SoapVar(
										array('session_id' => $session_id),
										SOAP_ENC_OBJECT
								)
				);
			} catch (SoapFault $exception) { }
			
			if ($this->auth_info) {
				return $resp = true;
			} else {
				return $resp = false;
			}
	  }
*/	  
	  /*Metodo para realizar la validacion del cliente*/
/*	  function check_customer_login($i_customer,$login,$pass){
			try {
				 $customer_client_info  = new SoapClient($this->wsdl_url_customer);
				 $customer_client_info->__setSoapHeaders($this->auth_info);

				 if($i_customer){
					$GetCustomerInfoRequest_info = array('i_customer' => $i_customer);

				}elseif($login){
					$GetCustomerInfoRequest_info = array('login' => $login);
				}
				
				if($GetCustomerInfoRequest_info){
				
				 $customer_response = $customer_client_info->get_customer_info($GetCustomerInfoRequest_info);
				 
				 if($customer_response){
					$this->customer_check = $customer_response->customer_info;
					if($this->customer_check->password == $pass){
						return $resp = true;
					}else{
						return $resp = false;
					}
				 }else{
					return $resp = false;
				 }
				}else{
					return $resp = false;
				}
		  } catch (SoapFault $exception) { } 
		}
*/	  
	  /* Metodo para agregar un Customer */
/*	  function addCustomer($customer)
	  {
		  try {            
				$status_add_customer = new SoapClient($this->wsdl_url_customer);
				$status_add_customer->__setSoapHeaders($this->auth_info);			
				$this->customer_info = $status_add_customer;            							   
				$status_request_add_customer = $status_add_customer->add_customer($customer);			
				if ($status_request_add_customer)
				{
					return $status_request_add_customer;
				}
				else
				{
					return "error";
				}
						
			} catch (SoapFault $exception) { 
				//echo $exception->faultcode;			
			}
	  }
	  
*/	  /* Metodo para eliminar un Customer */
/*	  function delCustomer($customer)
	  {
		  try {            
				$status_del_customer = new SoapClient($this->wsdl_url_customer);
				$status_del_customer->__setSoapHeaders($this->auth_info);			
				$this->customer_info = $status_del_customer;            							   
				$status_request_del_customer = $status_del_customer->delete_customer($customer);			
				if ($status_request_del_customer)
				{
					return $status_request_del_customer;
				}
				else
				{
					return "error";
				}
						
			} catch (SoapFault $exception) { 
				echo $exception->faultcode;			
			}
	  }
*/	  
	/* Metodo para agregar un Account  */
/*	  function addAccount($account)
	  {
		  try {            
				$status_add_account = new SoapClient($this->wsdl_url_account);
				$status_add_account->__setSoapHeaders($this->auth_info);			
				$this->account_info = $status_add_account;            							   
				$status_request_add_account = $status_add_account->add_account($account);			
				if ($status_request_add_account)
				{
					return $status_request_add_account;
				}
				else
				{
					return "error";
				}
						
			} catch (SoapFault $exception) { 
				//echo $exception->faultcode;			
			}
	  }
*/	  
	  /* Metodo para Inactivar una Cuenta  */
/*	  function desAccount($account)
	  {
		  try {            
				$status_add_account = new SoapClient($this->wsdl_url_account);
				$status_add_account->__setSoapHeaders($this->auth_info);			
				$this->account_info = $status_add_account;            							   
				$status_request_add_account = $status_add_account->terminate_account($account);			
				if ($status_request_add_account)
				{
					return $status_request_add_account;
				}
				else
				{
					return "error";
				}
						
			} catch (SoapFault $exception) { 
				echo $exception->faultcode;			
			}
	  }
*/	  
	  /* Generador de Clave */
/*	  function generar_clave(){
			$cadena_generadora= "h,R,E,W,g,9,8,F,Q,5,4,f,d,s,3,2,a,M,r,t,L,K,e,y,u,C,V,i,o,p,J,Z,X,B,w,l,k,j,z,x,N,H,G,0,1,q,c,v,O,I,b,n,m,D,S,A,P,U,7,6,Y,T";
			$arreglo_cadena = explode(',', $cadena_generadora);
			$tamano_clave = 12;
			$clave = '';
			for($i=0; $i<$tamano_clave; $i++)
			{
				$posicion = rand(0, (count($arreglo_cadena)-1));
				$clave = $clave.$arreglo_cadena[$posicion];
			}
			return $clave;
		}
		
		function update_dialing_rule($i_account, $values, $iteracion) {
		 //try {
				   if ($iteracion==1) {
				   $update_service_features = new SoapClient($this->wsdl_url_account);
				   $update_service_features->__setSoapHeaders($this->auth_info);
				   $this->update_service_features = $update_service_features;
				 }
				  
				 $serviceAttributeInfo1 = array('name'   => 'translate_cli_out',
											   'values' => array('N'));
											   
				 $serviceAttributeInfo2 = array('name'   => 'i_dial_rule',
												'values' => array($values));
												
				 $serviceAttributeInfo3 = array('name'   => 'translate_cli_in',
												'values' => array('N'));


				 $serviceFeatureInfo = array('flag_value'           => 'Y',
											 'effective_flag_value' => 'Y',
											 'name'                 => 'voice_dialing',
											 'attributes'           => array('ServiceAttributeInfo1' => $serviceAttributeInfo1,
																			 'ServiceAttributeInfo2' => $serviceAttributeInfo2,
																			 'ServiceAttributeInfo3' => $serviceAttributeInfo3));
				 if ($serviceFeatureInfo) {
					   if ($iteracion==1) { 
					   $update_service_features_response = $update_service_features->update_service_features(array('i_account'       => $i_account,
																												   'service_features' => array('ServiceFeatureInfo' => $serviceFeatureInfo)));
					 } else {
					   $update_service_features_response = $this->update_service_features->update_service_features(array('i_account'       => $i_account,
																														 'service_features' => array('ServiceFeatureInfo' => $serviceFeatureInfo)));
					 }

					 if ($update_service_features_response) {
						 $this->i_account = $update_service_features_response->i_account;
						 return $resp = true;
					 } else {
						 $this->i_account = "";
						 return $resp = false;
					 }
				 } else {
					 return $resp = false;
				 }
		 //} catch (SoapFault $exception){}
		}

		function update_rttp_level($i_account, $iteracion) {
		 //try {
				   if ($iteracion==1) {
				   $update_service_features = new SoapClient($this->wsdl_url_account);
				   $update_service_features->__setSoapHeaders($this->auth_info);
				   $this->update_service_features = $update_service_features;
				 }
				  
				$serviceFeatureInfo = array('name'                 => 'rtpp_level',																		 
											 'flag_value' 			=> "3",
											 'effective_flag_value' =>"3");			
				 if ($serviceFeatureInfo) {
					   if ($iteracion==1) { 
					   $update_service_features_response = $update_service_features->update_service_features(array('i_account'       => $i_account,
																												   'service_features' => array('ServiceFeatureInfo' => $serviceFeatureInfo)));
					 } else {
					   $update_service_features_response = $this->update_service_features->update_service_features(array('i_account'       => $i_account,
																														 'service_features' => array('ServiceFeatureInfo' => $serviceFeatureInfo)));
					 }

					 if ($update_service_features_response) {
						 $this->i_account = $update_service_features_response->i_account;
						 return $resp = true;
					 } else {
						 $this->i_account = "";
						 return $resp = false;
					 }
				 } else {
					 return $resp = false;
				 }
		 //} catch (SoapFault $exception){}
		}
		
		function update_custom_fields($idcustomer,$persona, $tipo, $clase)
		{
			//try 
			//{
				$update_custom = new SoapClient($this->wsdl_url_customer);
				$update_custom->__setSoapHeaders($this->auth_info);
				//$this->update_custom = $update_custom;
				
				$serviceAttributeInfo1 = array('name'=>'BOSS Persona','text_value'=>$persona);												   
				$serviceAttributeInfo2 = array('name'=>'BOSS Tipo Cliente','text_value'=>$tipo);											
				$serviceAttributeInfo3 = array('name'=>'BOSS Clase Cliente','text_value'=>$clase);
				// $serviceAttributeInfo4 = array('name'   => 'BOSS Abonado',
											   'text_value' => $abonado); 
								
				if ($this->updateCustom($update_custom, array("i_customer"=>$idcustomer, "custom_fields_values"=>array('CustomFieldsValuesInfo' => $serviceAttributeInfo1))))
					if ($this->updateCustom($update_custom, array("i_customer"=>$idcustomer, "custom_fields_values"=>array('CustomFieldsValuesInfo' => $serviceAttributeInfo2))))
						if ($this->updateCustom($update_custom, array("i_customer"=>$idcustomer, "custom_fields_values"=>array('CustomFieldsValuesInfo' => $serviceAttributeInfo3))))
							//if ($this->updateCustom($update_custom, array("i_account"=>$idcustomer, "custom_fields_values"=>$ServiceAttributeInfo4)))
								return true;
							//else 
								//return false;
						else 
							return false;
					else
						return false;
				else return false;
			//}
		}
		
		function update_account_fields($idaccount,$contrato)
		{
			//try 
			//{
				$update_account = new SoapClient($this->wsdl_url_account);
				$update_account->__setSoapHeaders($this->auth_info);
				//$this->update_custom = $update_custom;
				
				$serviceAttribute = array('name'   => 'BOSS Contrato','text_value' => $contrato);						   
								
				if ($this->updateCustom($update_account, array("i_account"=>$idaccount, "custom_fields_values"=>array('CustomFieldsValuesInfo' => $serviceAttribute))))
						return true;
				else return false;
			//}
		}
		
		function updateCustom ($link, $info)
		{
			$response=$link->update_custom_fields_values($info);
			if ($response)
				return true;
			else return false;
		}
		
		function get_customer_info($abonado) {
        try {
				$customer_client_info = new SoapClient($this->wsdl_url_customer);
				$customer_client_info->__setSoapHeaders($this->auth_info);

				$GetCustomerInfoRequest_info = array('name' => $abonado);

				if ($GetCustomerInfoRequest_info) {

					$customer_response = $customer_client_info->get_customer_info($GetCustomerInfoRequest_info);

					if ($customer_response->customer_info) {
						$this->customer_info = $customer_response->customer_info;
						return $resp = true;
					} else {
						$this->customer_info = "";
						return $resp = false;
					}
				} 
				else {
					return $resp = false;
				}
			} catch (SoapFault $exception) { }
		}
		
		function get_account($did) {
			try {
				  $customerAccount_list_info = new SoapClient($this->wsdl_url_account);
				  $customerAccount_list_info->__setSoapHeaders($this->auth_info);
				  				
				  $GetCustomerAccountRequest_info = array('id' => $did);
							
					if ($GetCustomerAccountRequest_info) {
						$customerAccount_list_response = $customerAccount_list_info->get_account_info($GetCustomerAccountRequest_info);
						
						if($customerAccount_list_response){
						  $this->account_info = $customerAccount_list_response->account_info;
						  //$this->customer_info = $customerAccount_list_response->customer_info;
						  return $resp = true;        
						}else{
						  $this->account_info = "";
						  return $resp = false;
						}
						
					}else{
						return $resp = false;
					}
			} catch (SoapFault $exception) { } 
		}
	*/
  }

 
  
// This returns the result
$HTTP_RAW_POST_DATA = file_get_contents("php://input");
$server->service($HTTP_RAW_POST_DATA);
?>
