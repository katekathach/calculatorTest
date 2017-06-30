<?php

require('vendor/autoload.php');
require('../includes/constants.php');
$calculatorTesting = true; // hack the untestability of the calculator.
require('../includes/class.calculator.php');

/**
 * Tests of Schletter core calculator class
 *
 */
class CalculatorTest extends \PHPUnit_Framework_TestCase {
   
   protected $excelFile;
   protected $calculator;
   protected $excelMappings = array(
       'Module Height'    => 'C5',
       'Module Width'     => 'C6',
       'Module Thickness' => 'C7',
       'Module Weight'    => 'C8',
       'Length of System' => 'C41',
       'Roof Design'      => 'C26',
       'Roof Slope'       => 'C23',
       'Wind Speed'       => 'C12',
       'Terrain Category' => 'C14',
       'Importance Category' => 'C16',
       'Ground Snow Load'    => 'C13', 
       'Rows'                => 'C35',
       'Columns'             => 'C36',
       'Module Orientation'  => 'C30',
       'Total System Weight' => 'C49', 
       'Building Height'     => 'C25',
   );
   
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
      $this->excelFile = \PHPExcel_IOFactory::load('excel/Residential Calc New (04-24-2013).xlsx');
      $this->calculator = new Calculator();
   }
   
   public function testPhoenixProject() { 
      $inputs = array(
          'Module Height'    => 1675,
          'Module Width'     => 1001,
          'Module Thickness' => 50,
          'Module Weight'    => 27,
          'Roof Design'      => 1,
          'Roof Slope'       => 1,
          'Wind Speed'       => 90,
          'Terrain Category' => 1,
          'Importance Category' => 1,
          'Ground Snow Load'    => 10,
          'Rows'                => 2,
          'Columns'             => 4,
          'Module Orientation'  => TRUE,
          'Building Height'     => 20,
      );
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
