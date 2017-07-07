<?php

require('vendor/autoload.php');



/**
 * Tests of Schletter core calculator class
 *
 */
class CalculatorTest extends \PHPUnit_Framework_TestCase {
   
   protected $excelFile;
   protected $calculator;
   protected $excelMappings = array(
       'Module Height'    => 'C8',
       'Module Width'     => 'C10',
       'Module Orientation' => 'C9',
        'Module Rows'=> 'C12',
        'Support Span' => 'C13' ,
        'Module Angle'=> 'C14',
        'Dead Load, Min' => 'C15',
        'Dead Load, Max' => 'C16' ,
        'ASCE' => 'C19',
        'Wind Speed' => 'C20',
        'Ground Snow Load' => 'C21' ,
        'Exposure Category' => 'C22' ,
        'Risk Category' => 'C23');
   
   protected $calculatorMappings = array(
       'Module Height'    => 'moduleHeightInMm',
       'Module Width'     => 'moduleWidthInMm',
       'Module Thickness' => 'moduleThicknessInMm',
       'Module Weight'    => 'moduleWeightInKg',
       'Roof Design'      => 'typeOfRoof',
       'Roof Slope'       => 'tiltOfTheRoofInDegrees',
       'Wind Speed'       => 'windSpeedInMph',
       'Terrain Category' => 'exposureCategory',
       'Ground Snow Load'    => 'groundSnowLoadInPsf', 
       'Rows'                => 'noOfRows',
       'Columns'             => 'noOfColumns',
       'Module Orientation'  => 'moduleOrientation',
       'Building Height'     => 'buildingHeightInFt',
       'Roof Connector'      => 'connectorType',
//       ''                    => 'layout',
//       ''                    => 'rafterSize',
//       ''                    => 'rafterSpacingInIn',
     
   );
   
   public function setUp() {
      $this->excelFile = \PHPExcel_IOFactory::load('MultiSpan Beam Calculator_Vb.xlsx');
      $this->calculator = new Calculator();
   }
   
   public function testPhoenixProject() { 
      $inputs = array(
          'Module Orientation' => "Portrait",
          'Module Height'    => 1675,
          'Module Width'     => 1001,
          'Module Columns' => 50,
          'Module Row'    => 27,
          'Support Span'      => 1,
          'Module Angle'       => 1,
         'Dead Load, Min'
=>1.75    ,
"Dead Load, Max" => 3.00  );

      $this->setExcelInputs($inputs);
      print_r($this->calculate($inputs));
      
      $expected = array(
          'Length of System' => 21.98,
          'Total System Weight' => 523.94,      
      );
      
      foreach ($expected as $field => $value) {
          $this->assertEquals($this->getValue($field), $value);
      }
      
   }
   
   protected function calculate($inputs) {
       foreach ($inputs as $field => $value) {
           // Variable variables. Don't panic!
           if (array_key_exists($field, $this->calculatorMappings)) {
               ${$this->calculatorMappings[$field]} = $value;
           }
       }
       return $this->calculator->calculate($typeOfRoof, $buildingHeightInFt, $tiltOfTheRoofInDegrees, $windSpeedInMph, 
           $groundSnowLoadInPsf, $exposureCategory, $moduleHeightInMm, $moduleWidthInMm, $moduleThicknessInMm,
           $moduleWeightInKg, $moduleOrientation, $noOfRows, $noOfColumns, '', array(), 0, 0);
   }
   
   protected function setExcelInputs($inputs) {
       $this->excelFile->setActiveSheetIndexByName('InPut & Calcs');
       $inputCalcsSheet = $this->excelFile->getActiveSheet();
       foreach ($inputs as $field => $value) {
           $inputCalcsSheet->SetCellValue($this->excelMappings[$field], $value);
       }
   }
   
   protected function getValue($field) {
       $this->excelFile->setActiveSheetIndexByName('InPut & Calcs');
       $inputCalcsSheet = $this->excelFile->getActiveSheet();
       return round($inputCalcsSheet->getCell($this->excelMappings[$field])->getCalculatedValue(), 2);
   }
}

?>
