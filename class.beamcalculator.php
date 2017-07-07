<?php 
/**
 * author: kateka
 * beam calculation
 */
require_once('includes/class.loadcalculator.php');

  class BeamCalculator extends LoadCalculator{
     
	 protected $tolerance = 50.80;
	 protected $baseLoad  = 1.00;
	 protected $supportSpan;
	 protected $supports;
	 protected $joints;
	 protected $span;
	 protected $rackLength;
	 protected $cantileverFt;
     protected $cantileverMM;
	 protected $railLoad = array();
	 protected $band = array();
	 protected $band2 = array();
	 protected $wValue = array();
	 protected $mValue = array();
	 protected $nValue = array();
	 protected $thomsjointc =array();
	 protected $temp , $temp2, $temp3, $temp4;
	 	
     function __construct($parameters) {
     	
         parent::__construct($parameters);		  
		  $this->railLoad =  array(
	 			"S1.5" => array("DeadLoad" => 1.50, "splice str" => 741, "splice wk" => 705.0)
	 		);
		   
     }
	 
	 public function output(){
	 	//parent::$this->parameters['moduleWidth'] * parent::$this->parameters['moduleColumn'] + 23 *(parent::$this->parameters['moduleColumn']-1) + $this->tolerance * 2
	 	$this->rackLength = $this->parameters['moduleWidth'] * $this->parameters['moduleColumn'] + 23 *($this->parameters['moduleColumn']-1) + $this->tolerance * 2;
		$rackLengthFt = $this->rackLength/304.8;
		$this->supportSpan = 304.8 * $this->parameters['span'];
		$supportSpanRd = $this->supportSpan;
		$Racktemp = $this->rackLength/$this->supportSpan;
		$this->supports =  max(2,$Racktemp);     //supports
		$this->joints = $this->joints + 2;       //joints
		$this->span = ceil($this->supports - 1); //span
		$this->cantileverMM =  ($this->rackLength - ($supportSpanRd  * $this->span)) / 2; //cantileverMM
		$this->cantileverFt = $cantileverMM / 304.8; //cantileverFeet
	  
	 }
	 
	 public function Axis(){
	 	$deadLoad = parent::DeadLoad();
	 	$snowLoad = parent::SnowLoad();
		$windLoad = parent::WindLoad();
        
	 	$DlMaxStrong = $deadLoad['appliedDlHigh'] + $this->railLoad['S1.5']['DeadLoad'] * cos(rad2deg($this->parameters['tilt']));
		$DlMinStrong = $deadLoad['appliedDlLow']  + $this->railLoad['S1.5']['DeadLoad'] * cos(rad2deg($this->parameters['tilt']));
		$Sl = $snowLoad['AppliedSl'] * cos(rad2deg($this->parameters['tilt']));
		$DlMaxWeak = $deadLoad['appliedDlHigh'] + $this->railLoad['S1.5']['DeadLoad'] + sin(rad2deg($this->parameters['tilt']));
		$DlMinWeak = $deadLoad['appliedDlLow']  + $this->railLoad['S1.5']['DeadLoad'] + sin(rad2deg($this->parameters['tilt']));
        
		return $DlMax . ";" .$DlMin . ";" . $Sl;
	 }
     
     public function Span(){
         	
         $spanArr = array();
         //$nValue = array();
		 $sum = array();
         $spanValue;
         //$wValue;
		 
        for ($i=0; $i < 19; $i++){             
            $spanArr[0] = $this->cantileverMM /304.8;
			
			$sum[$i] += $sum[$i];			
            if($i <= $this->supports  && ($sum[i] /$this->supports) <= ($i/2)){
               $spanArr[$i] = floatval($this->supportSpan / 304.8);
            }else{
                $spanArr[$i] = 0;
            }
          }
		
		 for ($i=0; $i < count($spanVal); $i++){
			if($i < (ceil($this->supports) -1) ){
            	$this->nValue[$i] = $i+ 1;
            }else{
            	$this->nValue[$i] = 0;
            }
		}
		
        return  $spanArr;
     
     }
	 
     public function claperonJoint(){
     	 $spanVal = $this->Span();	
     	 $cValue  = array();        
         $joint   = array();
         $RHS     = array();
		 $tempsp   = array();
		 $temp    = array();
		 $temp2   = array();
		 $temp3   = array();	
		 $temp4   = array();	
		 $this->temp = $temp;
		 $this->temp2 = $temp2;
		 $this->temp3 = $temp3;
		 $this->temp4 = $temp4;
         $joint[0]= 0;
      
	     $this->wValue[0] = $this->baseLoad * pow((($this->cantileverMM - $this->tolerance)/$this->cantileverMM),2);
		 $wValueRd = round($this->wValue[0],2); 
                  
        for ($i=0; $i < count($spanVal); $i++){
          	$joint[1] = round(-1 *$spanVal[0] / (6),2);
            $joint[$i] = round(-1 * ($spanVal[$i-1] / 6),2);			          
        }
		 
		
		for($i=0; $i <=20; $i++){
			//$cValue[0] = 0;
			//$cValue[1] = 1;
			if($i <= (ceil($this->supports) && $spanVal[$i-1] > 0)){
            	 $cValue[$i] = $i+1;
            }else{
            	$cValue[$i] = 0;
            }
		  
		}	 
	
		$spanlength = count($spanVal);
		 for ($j=0; $j < $spanlength; $j++){
		   $this->wValue[$j+1] = $this->baseLoad;
		  if($joint[$j+2] == 0  && $joint[$j] < 0){
			 $this->band[$j+2] = -1 * ($spanVal[$j-1]/(3) +  $spanVal[0]/(3));
           }else{
           	 if($joint[$j+1] == 0){
           	 	//$temp = array();
                $tempsp[$j] = 0;          	         
			 }else{
			 	$tempsp[$j]  = -1 * ($spanVal[$j-1]/(3) + ($spanVal[$j]/(3)));
			 }
			 	$this->band[$j+1] = $tempsp[$j];
           	   //$this->band[1] = -1 * ($spanVal[$j+1] /3  + (round($spanVal[0],2)/3));
            }   	 	   
		  
		}	
		  
		foreach ($cValue as $pos => $c){
			$supportplus1 = ceil($this->supports)+1;
			//echo $c;
			$this->band2[0] = 0;
			if($c == $supportplus1){
				$this->band2[$pos+1] = -1 * $spanVal[0]/6;
				$this->thomsjointc[$pos+1] = -1 * $spanVal[0]/6;
			}else{
				//$this->band2[12] = floatval(-1* $spanVal[0]/6);				
				$this->band2[$pos+1] =  floatval(-1* $spanVal[$pos]/6);
				$this->thomsjointc[$pos+1] = floatval(-1* $spanVal[$pos]/6);
			 }		
		}
		 
		
		for ($key=1;$key<=count($cValue); $key++){
		 	//foreach ($cValue as $key => $value) {				 
			 
		 		$supportplus1 = ceil($this->supports) +1;			
				//$RHS[0] = (pow(round($spanVal[0],2), 3) / (24)) * $this->wValue[0];	
				//$RHS[0] = $this->wValue[0] * pow($spanVal[0],3)/24;			
				$this->temp[$key] = $this->wValue[$key-1] * pow($spanVal[$key-1],3)/(24) ;	
				
			   if(($key-1) == ceil($this->supports)){				
				 $this->temp2[$key] =   $this->wValue[$key] * pow($spanVal[0],3)/(24) ; 			
			    }else{
				$this->temp2[$key] =  $this->wValue[$key] * pow($spanVal[$key],3)/(24);
			   }
			   
			  if(($key-1)== 2){		
				$this->temp3[$key] = $this->wValue[0] * (pow($spanVal[0],2)) * ($spanVal[$key] / (12));				
			    	}else{
				$this->temp3[$key] = 0;
			   }		
			  
			   if(($key+1) != $supportplus1 ){
			      //echo $key+1 . $supportplus1 ;
			      $this->temp4[$key] = 0;
				}else{			 
			      echo $this->temp4[$key] = (round($this->wValue[0],2) * pow($spanVal[0],2) / 2) * $spanVal[$key-1] /(6);
		       }
			 	  
	 	       $RHS[$key] = $this->temp[$key]  + ($this->temp2[$key]) - ($this->temp3[$key]) * 1 - ($this->temp4[$key] );
			   //$RHS[11] = ($temp[11] + $temp2[11]   -  $temp4[11]  -   $temp3[11] );
			  // ksort($RHS);
			  
		 }

	    echo "<pre>",print_r($this->temp2),"</pre>";
		echo "<pre>",print_r($this->temp3),"</pre>";
		//"<pre>". print_r($spanVal)."</pre>";
		//"<pre>". print_r($cValue)."</pre>";
		//print_r( $temp[0] . " " .  "temp 2: " .  $temp2[1] . " " . $temp3[1] . " " . "temp4" . ":" . $temp4[0]);
		//array("temp1" => $this->temp, "temp2" => $this->temp2, "temp3" => $this->temp3, "temp4"=> $this->temp4);
		return $RHS;
     
}

 public function calcRHS(){
		$temparrays = 	$this->claperonJoint();
		//echo "<pre>",print_r($temparrays),"</pre>";
		foreach ($temparrays as $pos => $tempval) {
			//echo "<pre>",print_r($tempval),"</pre>";
			foreach ($tempval as $tvpos => $tmpvalue) {
				//echo "<pre>",print_r($tmpvalue),"</pre>";
				//$rhs = $temparrays["temp1"] + $temparrays["temp2"] - $temparrays["temp3"] * 1- $temparrays["temp4"];
				
				if($pos == "temp1"){
					//echo "<pre>",$tmpvalue,"</pre>";
				}
			}
		}
		return $rhs;
}
	public function thomasJoint(){
	
		$M        = array();			
		$bVal     = $this->band;
		$cVal     = $this->band2;
		$f    	  = $this->claperonJoint(); //RHS array
		$nvalSpan = $this->Span();
		$thomasJointf = array();	
		$thomasJointb = array();	
		$start = 4;		
		$thomasJointf[0] = $f[0];
		$thomasJointf[1] = $f[1];
		$thomasJointf[2] = $f[2];
				
		//create the bvalue loop 
		$thomasJointb[0] = $bVal[1];
		$thomasJointb[1] = $bVal[2];	
		$thomasJointb[2] = $bVal[3];
		 foreach ($bVal as $pos => $value) {
		 	if($pos > 2){
		 	$thomasJointb[$pos] = $value -($cVal[$pos]/$thomasJointb[$pos-1]) * $this->thomsjointc[$pos-1];
		 }
		 }
			   
	   foreach ($f as $key => $fval) {
		   $frd[$key] = round($fval,2);
	   }
	   
       foreach ($frd as $pos => $value) {    	
        if($pos > 2){
        $thomasJointf[$pos] = $value - ( $cVal[$pos-1] / $thomasJointb[$pos-1]) * $thomasJointf[$pos-1];
		}  
       }	   
	   
	   //echo "f3" .($f[3] - ($cval[3] / $bval[2]) *($f[2]));
	   //echo print_r($cVal);
	   //echo print_r($bVal);        	 	
	   //echo print_r($thomasJointf);	
	   //echo print_r( $thomasJointb);	   		   			
	
	  foreach ($nvalSpan as $key => $value){
			   $M[0] = 0; 
				if($key == 0 ){					
					$M[$key] = -1 * abs($this->wValue[$key]) * (pow($value, 2)/2);
				}else if(($key+1) == 0){					
					$M[$key] = $this->wValue[$key] * pow($value[0],2) /2;
				}else{					
					if($key+1 == 0){$temp = 0;}else{$temp = $M[$key];}
					 $M[$key+1] =( $thomasJointf[$key+2] - ($cVal[$key+2] * $temp))/$bVal[$key+2] ;
					//echo $f[$key] . "<br>";
				}
		}
		
		return array("Bvalue_array" => $thomasJointb,"Cvalue_array"=> $cVal, "fValue_array" => $thomasJointf ,"m_array" => $M );
		//return $thomasJointf;
	}


public function ShearSpan(){
	$spanVal = $this->Span();
	$spanCantilever  = $spanVal[0];
	foreach ($spanVal as $pos => $sval) {
		if($sval == 0){
			return 0;
		}else{
			if($spanVal == $spanCantilever){				
			}
			//$this->mValue[$pos+2] -
		}
	}
}

public function MomentbySpan(){
	$momentByspan = array()	;
	$step = array();
	$max = 99;
	echo "<table>";
		for ($i=0; $i < 101; $i++) {
			$step[$i] = round($i / $max,2);
			$spanvalue = $this->Span(); 
			echo "<tr>";
			for ($j=0 ; $j < count($spanvalue) ; $j++) { 
				if($spanvalue[$j] == 0){	
					//$j = 0;
				}else{}
				//echo "<td>" .  ",".  $spanvalue[$j] . "</td>";
			}
			echo "</tr>";
			//echo print_r($step);
		}
		
	echo "</table>";
	}
 }
 
?>
