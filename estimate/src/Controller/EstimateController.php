<?php

/**
 * @file
 * Provides basic hello world message functionality.
 */

namespace Drupal\estimate\Controller;

use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\estimate\EstimateParseXML;
/**
 * Class EstimateController.
 *
 * @package Drupal\estimate\Controller
 */
class EstimateController extends ControllerBase {

  /**
   * webhook.
   *
   * @return array
   *   Markup.
   */
  public function webhook() {
    // get header
    header('X-SecureShare-Signature: *');

    // get data
    $postdata = file_get_contents("php://input");

    // parse xml
    $message = 'Ok';
    if (!empty($postdata)){
      try {
        $result = new EstimateParseXML();
        $result = $result->parseXML($postdata);
          $node = Node::create([
          'type'                                => 'estimate',
          'title'                               => 'Estimate',
          'field_estimatealtid'                 => ['value' => $result['EstimateAltID']],
          'field_totalamt'                      => ['value' => $result['TotalAmt1']],
          'field_totalamt_body'                 => ['value' => $result['TotalAmt2']],
          'field_totalhours_body'               => ['value' => $result['TotalHours']],
          'field_company_name'                  => ['value' => $result['CompanyName']],
          'field_actualpickupdatetime'          => ['value' => $result['ActualPickUpDateTime']],
          'field_roclosed'                      => ['value' => $result['ROClosed']],
          'field_insurance_company'             => ['value' => $result['InsuranceCompany']],
          'field_insurance_company_id'          => ['value' => $result['InsuranceCompanyID']],
          'field_estimator_name'                => ['value' => $result['EstimatorName']],
          'field_estimator_first_name'          => ['value' => $result['EstimatorFirstName']],
          'field_estimator_last_name'           => ['value' => $result['EstimatorLastName']],
          'field_estimator_id'                  => ['value' => $result['EstimatorID']],
          'field_arrival_date_time'             => ['value' => $result['ArrivalDateTime']],

          'field_parts_type_aftermarket'        => ['value' => $result['PAA_TotalAmt']],
          'field_parts_type_glass'              => ['value' => $result['PAG_TotalAmt']],
          'field_parts_type_new_partial'        => ['value' => $result['PAL_TotalAmt']],
          'field_parts_type_new_oem'            => ['value' => $result['PAM_TotalAmt']],
          'field_parts_type_new_oem_discoun'    => ['value' => $result['PAN_TotalAmt']],
          'field_parts_type_other'              => ['value' => $result['PAND_TotalAmt']],
          'field_parts_type_re_cored'           => ['value' => $result['PAO_TotalAmt']],
          'field_parts_type_recycled_oe'        => ['value' => $result['PAP_TotalAmt']],
          'field_parts_type_remanufactured'     => ['value' => $result['PAR_TotalAmt']],
          'field_parts_type_sublet'             => ['value' => $result['PAS_TotalAmt']],

          'field_sales_other_hazardous_wast'    => ['value' => $result['MAHW_TotalAmt']],
          'field_sales_other_paint_material'    => ['value' => $result['MAPA_TotalAmt']],
          'field_sales_other_shop_materials'    => ['value' => $result['MASH_TotalAmt']],
          'field_sales_other_user_defined_1'    => ['value' => $result['OT1_TotalAmt']],
          'field_sales_other_user_defined_2'    => ['value' => $result['OT2_TotalAmt']],
          'field_sales_other_user_defined_3'    => ['value' => $result['OT3_TotalAmt']],
          'field_sales_other_user_defined_4'    => ['value' => $result['OT4_TotalAmt']],
          'field_sales_other_storage'           => ['value' => $result['OTST_TotalAmt']],
          'field_sales_other_towing'            => ['value' => $result['OTTW_TotalAmt']],
        ]);

        $node->save();
        // save to xml
        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($postdata);
        $time = time();
        $path = realpath(__DIR__.'/../../');
        $domxml->save($path."/xml/estimate{$time}.xml");
        $message = $this->t($message);
      } catch (\Throwable $e){
        \Drupal::logger('php')->error('Estimate Error: ' . $e->getMessage());
        $message = $e->getMessage();
      }
    } else {
      $message = "Empty data";
    }

    return ['#markup' => $message];
  }


}
