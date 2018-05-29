<?

class NFE {
	const API_KEY = "";
	const URL = "https://api.nfe.io";
	const VERSION = "1.0";
	const COMPANIES = "companies";
	const SERVICE_INVOICES = "serviceinvoices";
	const CERTIFICATES = "certificate";
	const POST = "POST";
	const GET = "GET";
	const PUT = "PUT";
	const DELETE = "DELETE";
	const ENVIRONMENT = "Production	"; //['Development', 'Production', 'Staging']

	public static function Call($method, $service, $data = false, $multipart = false, $retornoURLRedi = false)
	{
		$url = sprintf("%s/%s/%s", self::URL, self::VERSION, $service);
		$curl = curl_init();

		switch ($method)
		{
			case self::POST:
				curl_setopt($curl, CURLOPT_POST, true);
				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				break;
			case self::PUT:
				curl_setopt($curl, CURLOPT_PUT, true);
				break;
			case self::GET:
				if ($data)
				$url = sprintf("%s/%s/%s?%s", self::URL, self::VERSION, $service, http_build_query($data));
				break;
			case self::DELETE:
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
				break;
		}

		if($retornoURLRedi){
			curl_setopt($curl, CURLOPT_HEADER, true);
		}

		// Autenticação e cabeçalhos:
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: '.self::API_KEY,
			'Aczipt: application/json'
		));

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($curl);
 
		curl_close($curl);

 		if($retornoURLRedi){

			preg_match('/Location:(.*)$/m', $result, $matches);
			$urlRedir = trim($matches[1]);
			return $urlRedir;

		} else {

			return json_decode($result);

		}
		
	}
	

	public static function newInvoice($companyid, $cityServiceCode, $name, $email, $federalTaxNumber, $country, $postalCode, $street, $number, $additionalInformation, $district, $cityCode, $cityName, $state, $description, $servicesAmount) {

		$data = array(
			'cityServiceCode' => $cityServiceCode,
			'borrower' => array (
					'name' => $name,
					'federalTaxNumber' => $federalTaxNumber,
					'email' => $email,
					'address' => array (
						'country' => $country,
						'postalCode' => $postalCode,
						'street' => $street,
						'number' => $number,
						'additionalInformation' => $additionalInformation,
						'district' => $district,
						'city' => array (
							'code' => $cityCode,
							'name' => $cityName
						),
						'state' => $state
					)
			),
			'description' => $description,
			'servicesAmount' => $servicesAmount,
			'issRate' => 0.03,
		    'issTaxAmount' => 0,
		    'irAmountWithheld' => 0,
		    'pisAmountWithheld' => 0,
		    'cofinsAmountWithheld' => 0,
		    'csllAmountWithheld' => 0,
		    'inssAmountWithheld' => 0,
		    'issAmountWithheld' => 0,
		    // 'issuedOn' => '2018-04-30',
		    'othersAmountWithheld' => 0
		);

		return NFE::Call(self::POST, self::COMPANIES . "/" . $companyid . "/" . self::SERVICE_INVOICES, $data);
	}

	public static function newCompany($nome, $federalTaxNumber, $email, $country, $street, $number, $openningDate, $taxRegime, $legalNature, $municipalTaxNumber, $zip, $district, $cityCode, $cityName, $provincy) {

		$data = array(
			'name'      => $nome,
			'tradeName' => $nome,
			'federalTaxNumber'    => $federalTaxNumber,
			'email'       => $email,
			'address' => array (
				'country' => $country,
				'postalCode' => $zip,
				'street' => $street,
				'number' => $number,
				'district' => $district,
				'city' => array (
					'code' => $cityCode,
					'name' => $cityName
				),
				'state' => $provincy
			),
			'openningDate' => $openningDate,
			'taxRegime' => $taxRegime,
			'legalNature' => $legalNature,
			'municipalTaxNumber' => $municipalTaxNumber,
			'environment' => self::ENVIRONMENT
		);

		return NFE::Call(self::POST, self::COMPANIES, $data);
	}

	public static function companyCertificate($idcompany, $certificado, $senha) {
		$base = self::URL . "/" . self::VERSION . "/" . self::COMPANIES . "/" . $idcompany . "/" . self::CERTIFICATES;
		$headers = array(
			"Content-Type: multipart/form-data",
			'Authorization: '.self::API_KEY,
			'Aczipt: application/json'
		);
		$postfields = array("file" => "@".$certificado, "password" => $senha);
		$ch = curl_init();

		$options = array(
			CURLOPT_URL => $base,
			CURLOPT_HEADER => true,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => $postfields,
			CURLOPT_INFILESIZE => $filesize,
			CURLOPT_RETURNTRANSFER => true
		);

		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	public static function companyDelete($idcompany) {
		return NFE::Call(self::DELETE, self::COMPANIES . "/" . $idcompany);
	}

	public static function companyDetail($federalTaxNumber) {
		return NFE::Call(self::GET, self::COMPANIES . "/" . $federalTaxNumber);
	}

	public static function invoiceDetail($idcompany, $id) {
		return NFE::Call(self::GET, self::COMPANIES . "/" . $idcompany . "/" . self::SERVICE_INVOICES . "/" . $id);
	}


	public static function download($company_id, $nfe_id, $type = 'pdf'){

		 
		$service = NFE::COMPANIES . '/' . $company_id . '/serviceinvoices/'. $nfe_id . '/' . $type ;
		$file = NFE::Call(NFE::GET, $service,false,false,true); 

		if(copy($file, __RAIZ__ . 'tmp/pdf/' . $nfe_id . '.' . $type )){
			return true;
		} else {
			return false;
		}
	  
	}

	public static function getServiceInvoices($company_id,$pageCount = 100,$pageIndex = 1){ 
		 
		$service = NFE::COMPANIES . '/' . $company_id . '/serviceinvoices'  ;

		$SERVICE_INVOICES = NFE::Call(NFE::GET, $service,array('pageCount' => $pageCount,'pageIndex' => $pageIndex),false,false); 
 	
 		return $SERVICE_INVOICES;

 
	  
	}
	public static function invoiceCancelation($company_id,$nfe_id){ 
		 
		$service = NFE::COMPANIES . '/' . $company_id . '/serviceinvoices/' . $nfe_id  ;

		$SERVICE_INVOICES = NFE::Call(NFE::DELETE, $service,false,false,false); 
 	
 		return $SERVICE_INVOICES; 
	  
	}
}

?>
