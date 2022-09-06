<?php

namespace Drupal\estimate;

class EstimateParseXML {

  const SALES_PARTS = array(
    'PAA'   => true,
    'PAG'   => true,
    'PAL'   => true,
    'PAM'   => true,
    'PAN'   => true,
    'PAND'  => true,
    'PAO'   => true,
    'PAP'   => true,
    'PAR'   => true,
    'PAS'   => true
  );

  const SALES_OTHER = array(
    'MAHW'  => true,
    'MAPA'  => true,
    'MASH'  => true,
    'OT1'   => true,
    'OT2'   => true,
    'OT3'   => true,
    'OT4'   => true,
    'OTST'  => true,
    'OTTW'  => true
  );

  const SALES_LABOR = array(
    'OP0'  => true,
    'OP2'  => true,
    'OP4'  => true,
    'OP5'  => true,
    'OP6'  => true,
    'OP9'  => true,
    'OP10' => true,
    'OP11' => true,
    'OP15' => true,
    'OP16' => true,
    'OP26' => true
  );

  protected $xml;
  protected $result;

  public function __construct($xml,$result)
  {
    $this->xml = $xml;
    $this->result = $result;
  }

  public function parseXML($postdata){

    $this->xml = new \SimpleXMLElement($postdata);
    if($this->xml->DocumentInfo && $this->xml->DocumentInfo->ReferenceInfo && $this->xml->DocumentInfo->ReferenceInfo->OtherReferenceInfo){
      $other_info = $this->xml->DocumentInfo->ReferenceInfo->OtherReferenceInfo;
      foreach ($other_info as $info){
        if($info->OtherReferenceName == 'EstimateAltID'){
          $this->result['EstimateAltID'] = (string) $info->OtherRefNum;
          break;
        }
      }
    }

    if($this->xml->RepairTotalsInfo && $this->xml->RepairTotalsInfo->SummaryTotalsInfo){
      $other_info = $this->xml->RepairTotalsInfo->SummaryTotalsInfo;
      foreach ($other_info as $info){
        if($info->TotalSubType == 'T2'){
          $this->result['TotalAmt1'] = (float) $info->TotalAmt;
          break;
        }
      }
    }

    if($this->xml->RepairTotalsInfo && $this->xml->RepairTotalsInfo->LaborTotalsInfo){
      $other_info = $this->xml->RepairTotalsInfo->LaborTotalsInfo;
      foreach ($other_info as $info){
        if($info->TotalType == 'LAB'){
          $this->result['TotalHours'] = (float) $info->TotalHours;
          $this->result['TotalAmt2'] = (float) $info->TotalAmt;
          break;
        }
      }
    }

    // More fields
    $this->_fieldGroupMorefields();

    // Field Group: Insurance
    $this->_fieldGroupInsurance();

    // Field Group: Estimator
    $this->_fieldGroupEstimator();

    // Field Group: Cycle Time
    $this->_fieldGroupCycleTime();

    // Field Group: Sales Parts
    $this->_fieldGroupSalesParts();

    // Field Group: Sales Other
    $this->_fieldGroupSalesOther();

    // Field Group: Sales Labor
    $this->_calcAmtHours();

    return $this->result;
  }

  protected function _fieldGroupMorefields()
  {
    if($this->xml->AdminInfo && $this->xml->AdminInfo->RepairFacility && $this->xml->AdminInfo->RepairFacility->Party->OrgInfo){
      $other_info = $this->xml->AdminInfo->RepairFacility->Party->OrgInfo;
      $this->result['CompanyName'] = $this->_searchFieldXML($other_info,'CompanyName');
    }

    if($this->xml->EventInfo && $this->xml->EventInfo->RepairEvent){
      $other_info = $this->xml->EventInfo->RepairEvent;
      $this->result['ActualPickUpDateTime'] = $this->_searchFieldXML($other_info,'ActualPickUpDateTime');
      $this->result['ROClosed'] = !empty($this->result['ActualPickUpDateTime']);
    }
  }

  protected function _fieldGroupInsurance()
  {
    if($this->xml->AdminInfo && $this->xml->AdminInfo->InsuranceCompany && $this->xml->AdminInfo->InsuranceCompany->Party->OrgInfo) {
      $other_info = $this->xml->AdminInfo->InsuranceCompany->Party->OrgInfo;
      $this->result['InsuranceCompany'] = $this->_searchFieldXML($other_info, 'CompanyName');
      $this->result['InsuranceCompanyID'] = $this->_searchFieldXML($other_info->IDInfo,'IDNum');
    }
  }

  protected function _fieldGroupEstimator()
  {
    if($this->xml->EstimatorIDs && $this->xml->EstimatorIDs->CurrentEstimatorID){
      $other_info = $this->xml->EstimatorIDs;
      $this->result['EstimatorName'] = $this->_searchFieldXML($other_info, 'CurrentEstimatorID');
    }

    if($this->xml->AdminInfo && $this->xml->AdminInfo->Estimator && $this->xml->AdminInfo->Estimator->Party->PersonInfo->PersonName && $this->xml->AdminInfo->Estimator->Party->PersonInfo->IDInfo){
      $other_info = $this->xml->AdminInfo->Estimator->Party->PersonInfo;
      $this->result['EstimatorFirstName'] = $this->_searchFieldXML($other_info->PersonName, 'FirstName');
      $this->result['EstimatorLastName'] = $this->_searchFieldXML($other_info->PersonName, 'LastName');
      $this->result['EstimatorID'] = $this->_searchFieldXML($other_info->IDInfo,'IDNum');
    }
  }

  protected function _fieldGroupCycleTime()
  {
    if($this->xml->EventInfo && $this->xml->EventInfo->RepairEvent){
      $other_info = $this->xml->EventInfo->RepairEvent;
      $this->result['ArrivalDateTime'] = $this->_searchFieldXML($other_info,'ArrivalDateTime');
    }
  }

  protected function _fieldGroupSalesParts()
  {
    if($this->xml->RepairTotalsInfo && $this->xml->RepairTotalsInfo->PartsTotalsInfo){
      $other_info = (array) $this->xml->RepairTotalsInfo;
      foreach ($other_info['PartsTotalsInfo'] as $PartsTotalsInfo => $info){
        $type = (string) $info->TotalType;
        if (!empty(static::SALES_PARTS[$type])){
          $this->result[$info->TotalType . '_TotalAmt'] = (float) $info->TotalAmt;
        }
      }
    }
  }

  protected function _fieldGroupSalesOther()
  {
    if($this->xml->RepairTotalsInfo && $this->xml->RepairTotalsInfo->OtherChargesTotalsInfo){
      $other_info = (array) $this->xml->RepairTotalsInfo;
      foreach ($other_info['OtherChargesTotalsInfo'] as $OtherChargesTotalsInfo => $info){
        $type = (string) $info->TotalType;
        if (!empty(static::SALES_OTHER[$type])){
          $this->result[$info->TotalType . '_TotalAmt'] = (float) $info->TotalAmt;
        }
      }
    }
  }

  protected function _searchFieldXML($other_info, $infoName)
  {
    $result = "";
    $data = (array)$other_info;

    if (isset($data[$infoName])) {
      $result = (string)$data[$infoName];
    }

    return $result;
  }

  protected function _calcAmtHours()
  {
    if (!empty($this->xml->DamageLineInfo)){
      foreach ($this->xml->DamageLineInfo as $elements => $DamageLineInfo){
        foreach ($DamageLineInfo as $elementInfo) {
          $type = (string)$elementInfo->LaborOperation;
          if (!empty(static::SALES_LABOR[$type])) {
            $this->result[$elementInfo->LaborOperation . '_TotalAmt'] = $this->result[$elementInfo->LaborOperation . '_TotalAmt'] + $elementInfo->LaborAmt;
            $this->result[$elementInfo->LaborOperation . '_TotalHours'] = $this->result[$elementInfo->LaborOperation . '_TotalHours'] + $elementInfo->LaborHours;
            break;
          }
        }
      }
    }
  }
}

