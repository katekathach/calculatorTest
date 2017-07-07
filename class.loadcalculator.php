<?php
/**
 * 
 */
class LoadCalculator  {
	
	protected $parameters = array();
    public $lengthHighFt;
	public $lengthLowFt;
	
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
		
    }
	
	public function KzFactorNew(){
		$Kz = array(
			  "B" => array("Ce" => 0.9, "Zg" => 1200, "a" => 7,  "Zm" => 30),
		      "C" => array("Ce" =>0.9 , "Zg" => 900, "a" => 9.5, "Zm" => 15),
		      "D" => array("Ce" =>0.8, "Zg" => 700, "a" =>11.5,  "Zm" =>  7)
		     );
			 return $Kz;
	}
	
	public function GCPFactors(){
		$GCP = array(
			   "15" => array("gcp+" => 1.600, "gcp-" => -2.300),
			   "20" => array("gcp+" => 1.650, "gcp-" => -2.400),
			   "25" => array("gcp+" => 1.700, "gcp-" => -2.500),
			   "30" => array("gcp+" => 1.850, "gcp-" => -2.600),
			   "35" => array("gcp+" => 2.000, "gcp-" => -2.700)
			   
		);
		return $GCP;
	}
	
	
	//array_search($this->parameters['tilt'], $);
	
	public function DeadLoad(){
	
		$lengthHigh; 
		$lengthLow;
		$DeadLoadHigh = floatval(3.00); //psf
		$DeadLoadLow  = floatval(1.75); //psf
		$appliedDlHigh;
		$appliedDlLow;
		
		//module distribution
		$portrait  = floatval(0.5);
		$landscape = floatval(1.00); 
		
		if($this->parameters['cellType'] == 60){
			$lengthHigh = 1700;  //mm
			$lengthLow  = 1550;  //mm
		}else{
			$lengthHigh = 2000;  //mm
			$lengthLow  = 1900;  //mm
		}
		 
		 //echo $this->parameters['cellType'];
		 $this->lengthHighFt = $lengthHigh  / 304.8;   //ft		
		 $this->lengthLowFt  = $lengthLow   / 304.8;   //ft
			
		if($this->parameters['orientation'] == "portrait"){				
			$appliedDlHigh =  $portrait * $DeadLoadHigh * $this->lengthHighFt;
			$appliedDlLow  =  $portrait * $DeadLoadLow  * $this->lengthLowFt;
		}else{
			$appliedDlHigh = $landscape * $DeadLoadHigh * $this->lengthHighFt;
			$appliedDlLow  = $landscape * $DeadLoadLow  * $this->lengthLowFt;
		}
			return array("lengthHighFt" => $this->lengthHighFt, "lengthLowFt" => $this->lengthLowFt, "appliedDlHigh" => $appliedDlHigh, "appliedDlLow" => $appliedDlLow);
	}
	
	
	
	public function CeFactor(){
		$KzFactors  = $this->KzFactorNew();
		$CeValue;		
		foreach ($KzFactors as $key => $Ceval ) {
		  foreach ($Ceval as $keys => $val ) {
			if($this->parameters['exposure'] == $key){
			    $CeValue = $Ceval;
				}
			}
		}
		return $CeValue;
	}
			
	public function importanceFactor(){
		$importance;
		 $importanceArr = array( "I"=>  0.8,"II" =>  1,"III" => 1.1,"IV" => 1.2);
		foreach ($importanceArr  as $key => $importanceVal) {
			if($this->parameters['importance'] == $key){
			    $importance  = $importanceVal;
			}
		}
		return $importance;
	}
	
	public function SnowLoad(){
		$Ct = 1.2;
        $slopeArray = $this->CsSlope();
		$importance = $this->importanceFactor();
		$CeValue    = $this->CeFactor();
        $slopeArray["slope"];		
		$Pfmin20;
		$Pfmin25;      
		
		$Pf = (0.7) * ($CeValue["Ce"]) * (1.2) * ($importance) * ($this->parameters["snow"]);
		
		//Pfmin 20
		if($this->parameters['tilt'] <= 15){
			$Pfmin20 = 0;
		}else if($this->parameters['snow'] < 20){
			$Pfmin20 = 20 * $importance;
		}else{
			$Pfmin20 = $importance;
		}
		//Pfmin 25
		if($this->parameters['tilt'] <= 15){
			$Pfmin25 = 0;
		}else if($this->parameters['snow'] < 20){
			$Pfmin25 = $importance;			
		}else{
			$Pfmin25 = 20 * $importance;
		}
		
		if($Pf > 20 && $Pf > $Pfmin20){
			$Ps = $Pfmin20 * $slopeArray['slope'];
		}else if($Pfmin25 > $Pf){
			$Ps = $Pfmin25 * $slopeArray['slope'];
		}else{
			$Ps = $Pf * $slopeArray['slope'];
		}
				
		if($this->parameters['orientation'] == "portrait"){
			//portrait = 0.5		
		$appliedSL = .5 * $Ps * $this->lengthHighFt * cos(deg2rad($this->parameters['tilt']));
		}
		$snowLoad = array("Ce" => $CeValue["Ce"] , "Ct" => $Ct , "Cs" => $slopeArray["slope"], "importance" => $importance, "Pg" => $this->parameters['snow'] 
		, "Pf" => $Pf , "Pfmin20" => $Pfmin20 , "Pfmin25" => $Pfmin25, "Ps" => $Ps , "AppliedSl" => $appliedSL);
		return $snowLoad;
	}

	public function WindLoad(){
		$Kzt = 1;
		$Kd = 0.85;
		$Gfactor = .85;
		$I = 1; //code 7-10
		$Windspeed = $this->parameters['wind'];
		$WindspeedPow = pow($Windspeed , 2);
		$this->CeFactor();
		$KzArray = $this->KzFactorNew();
	    $KzAval = $KzArray[$this->parameters['exposure']][a];
		$KzAvalpow = (2/$KzAval);		
		
		if($this->parameters['height'] < 15){
			$windKz =  (( $KzArray[$this->parameters['exposure']]['Zm'] )/$KzArray[$this->parameters['exposure']]['Zg']);
			$windKz = pow($windKz , $kzAvalpow);
			$windKz = $windKz * 2.01;  
		}else{
			$maxvalue = max(( $KzArray[$this->parameters['exposure']]['Zm'] ),$this->parameters['height']);
			$maxvalue = $maxvalue /$KzArray[$this->parameters['exposure']]['Zg'];
			$windKz= pow($maxvalue,$KzAvalpow);
			$windKz= 2.01 * $windKz; 
        }
		
		//$this->$parameters['height'];
		$qz = .00256 * round($windKz,2) * $Kzt * $Kd * $WindspeedPow * $I * $Gfactor;
		
		$pressure = $this->GCPFactors();
		if($this->parameters['orientation'] == "portrait"){
			//portrait = 0.5		
		$appliedWlplus = .5 * $qz * $this->lengthHighFt * $pressure[15]['gcp+'];
		}
		
		if($this->parameters['orientation'] == "portrait"){
			//portrait = 0.5		
		$appliedWlminus = .5 * $qz * $this->lengthHighFt * $pressure[15]['gcp-'];
		}
		
		return  array("windKz" => $windKz,"a" => $KzAval, "exposure Zg" => $KzArray[$this->parameters['exposure']]['Zg'] , "height" => $this->parameters['height'] 
		, "wind speed " => $WindspeedPow  , "Gz" => $qz ,"appliedWl+" => $appliedWlplus, "appliedWl-" => $appliedWlminus);
	}
	
	public function CsSlope(){
		$this->parameters['tilt'] = 15;
		$slope      = 15;      //default min
		$slopeHigh  = 70 ;     //high	
		$Cs         = 0.00;	
		$slopeInfoArr= array("tilt" => $this->parameters['tilt'] , "slope" => "");
		$slopeArray = array("slopeMin" => array($slope => 1) , "slopeHigh" => array($slopeHigh => 0));				
		if($this->parameters['tilt'] == 15 ){
			 $slopeInfoArr['slope'] = 1;
		}else if($this->parameters['tilt'] == 70){
			 $slopeInfoArr['slope'] = 0;
		}else if($this->paremeters['tilt'] < $slope){
		     $slopeInfoArr['slope'] = $slopeArray["slopeMin"][15];
		}else{
			 $slopeInfoArr['slope'] = 1 + (0-1)*($this->parameters['tilt']-$slope)/($slopeHigh - $slope);
		}
		
        return $slopeInfoArr;  //print_r($slopeInfoArr);
					
	}
}



?>