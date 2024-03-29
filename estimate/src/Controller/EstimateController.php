<?php

/**
 * @file
 * Provides basic hello world message functionality.
 */

namespace Drupal\estimate\Controller;

use Drupal\Core\Datetime\DrupalDateTime;
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
        $result = new EstimateParseXML($postdata);
        $result = $result->parseXML();

        // get status for estimate
        $status = 1;
        if (!is_duplicate_estimate($result) && $result['ROClosed']){
            $status = 0;
        }

        $targetIdCompany = $targetIdInsuranceCompany = $targetIdEstimator = "";
        $types = array("CompanyID", "EstimatorID", "InsuranceCompanyIDNum");
        foreach ($types as $type){

          if ($type == "InsuranceCompanyIDNum"){
            $nids = \Drupal::entityQuery('node')->condition('field_insurance_company_id',$result[$type])->execute();
            $nodes =  Node::loadMultiple($nids);
            if (empty($nodes)) {
              $nodeInsuranceCompany = Node::create([
                'type'                          => 'insurance_company',
                'title'                         => $result["InsuranceCompany"],
                'field_insurance_company_id'    => ['value' => $result["InsuranceCompanyIDNum"]],
                'field_ins_company'             => ['value' => $result["InsuranceCompany"]],
              ]);

              $nodeInsuranceCompany->save();
              $targetIdInsuranceCompany = $nodeInsuranceCompany->id();
            } else {
              $targetIdInsuranceCompany = array_shift($nodes)->id();
            }
          }
            if ($type == "CompanyID"){
              $nids = \Drupal::entityQuery('node')->condition('field_com_id_com',$result[$type])->execute();
              $nodes =  Node::loadMultiple($nids);
                if (empty($nodes)) {
                    $nodeCompany = Node::create([
                        'type'                 => 'company',
                        'title'                => $result['CompanyName'],
                        'field_com_id_com'     => ['value' => $result['CompanyID']],
                        'field_com_name_com'   => ['value' => $result['CompanyName']],
                    ]);

                    $nodeCompany->save();
                    $targetIdCompany = $nodeCompany->id();
                } else {
                    $targetIdCompany = array_shift($nodes)->id();
                }
            }

            if ($type == "EstimatorID"){
              $nids = \Drupal::entityQuery('node')->condition('field_est_id',$result[$type])->execute();
              $nodes =  Node::loadMultiple($nids);
                if (empty($nodes)) {
                    $nodeEstimator = Node::create([
                        'type'                     => 'estimator',
                        'title'                    => "{$result['EstimatorFirstName']} {$result['EstimatorLastName']}",
                        'field_est_first_name'     => ['value' => $result['EstimatorFirstName']],
                        'field_est_last_name'      => ['value' => $result['EstimatorLastName']],
                        'field_est_id'             => ['value' => $result['EstimatorID']],
                    ]);

                    $nodeEstimator->save();
                    $targetIdEstimator = $nodeEstimator->id();
                } else {
                    $targetIdEstimator = array_shift($nodes)->id();
                }
            }
        }

          if ($result['ActualPickUpDateTime']){
              $CreationDateTime = str_replace(' - ', ' ', $result['ActualPickUpDateTime']);
              $date_time = new DrupalDateTime($CreationDateTime, new \DateTimeZone('UTC'));
              $pickUpDate = $date_time->format('Y-m-d\TH:i:s');
          } else {
              $pickUpDate = '';
              $result['ActualPickUpDateTime'] = '';
          }

        $nodeEstimate = Node::create([
          'type'                                => 'estimate',
          'title'                               => 'Estimate',
          'status'                              => $status,

          'field_esaltid'                       => ['value' => $result['EstimateAltID']],
          'field_totalamt'                      => ['value' => $result['TotalAmt1']],
          'field_totalamt_b'                    => ['value' => $result['LABTotalAmt2']],
          'field_totalhours_b'                  => ['value' => $result['LABTotalHours']],
          'field_totalhours_p'                  => ['value' => $result['LARTotalHours']],
          'field_totalamt_p'                    => ['value' => $result['LARTotalAmt2']],
          'field_com_name_es'                   => ['target_id' => $targetIdCompany],
          'field_pickup_dt'                     => ['value' => $result['ActualPickUpDateTime']],
          'field_roclosed'                      => ['value' => $result['ROClosed']],
          'field_insurance_company'             => ['target_id' => $targetIdInsuranceCompany],
          'field_com_id_es'                     => ['value' => $result['CompanyID']],
          'field_creationdt'                    => ['value' => $result['CreationDateTime']],
          'field_pickup_date_time'              => ['value' => $pickUpDate],
          'field_es_name'                       => ['value' => $result['EstimatorName']],
          'field_arrival_dt'                    => ['value' => $result['ArrivalDateTime']],
          'field_estimator_id'                  => ['target_id' => $targetIdEstimator],

          'field_pt_paa'                        => ['value' => $result['PAA_TotalAmt']],
          'field_pt_pag'                        => ['value' => $result['PAG_TotalAmt']],
          'field_pt_pal'                        => ['value' => $result['PAL_TotalAmt']],
          'field_pt_pam'                        => ['value' => $result['PAM_TotalAmt']],
          'field_pt_pan'                        => ['value' => $result['PAN_TotalAmt']],
          'field_pt_pand'                       => ['value' => $result['PAND_TotalAmt']],
          'field_pt_pao'                        => ['value' => $result['PAO_TotalAmt']],
          'field_pt_pap'                        => ['value' => $result['PAP_TotalAmt']],
          'field_pt_par'                        => ['value' => $result['PAR_TotalAmt']],
          'field_pt_pas'                        => ['value' => $result['PAS_TotalAmt']],

          'field_so_mahw'                       => ['value' => $result['MAHW_TotalAmt']],
          'field_so_mapa'                       => ['value' => $result['MAPA_TotalAmt']],
          'field_so_mash'                       => ['value' => $result['MASH_TotalAmt']],
          'field_so_ot1'                        => ['value' => $result['OT1_TotalAmt']],
          'field_so_ot2'                        => ['value' => $result['OT2_TotalAmt']],
          'field_so_ot3'                        => ['value' => $result['OT3_TotalAmt']],
          'field_so_ot4'                        => ['value' => $result['OT4_TotalAmt']],
          'field_so_otst'                       => ['value' => $result['OTST_TotalAmt']],
          'field_so_ottw'                       => ['value' => $result['OTTW_TotalAmt']],

          'field_op0_amount'                    => ['value' => $result['OP0_TotalAmt']],
          'field_op0_hours'                     => ['value' => $result['OP0_TotalHours']],
          'field_op2_amount'                    => ['value' => $result['OP2_TotalAmt']],
          'field_op2_hours'                     => ['value' => $result['OP2_TotalHours']],
          'field_op4_amount'                    => ['value' => $result['OP4_TotalAmt']],
          'field_op4_hours'                     => ['value' => $result['OP4_TotalHours']],
          'field_op5_amount'                    => ['value' => $result['OP5_TotalAmt']],
          'field_op5_hours'                     => ['value' => $result['OP5_TotalHours']],
          'field_op6_amount'                    => ['value' => $result['OP6_TotalAmt']],
          'field_op6_hours'                     => ['value' => $result['OP6_TotalHours']],
          'field_op9_amount'                    => ['value' => $result['OP9_TotalAmt']],
          'field_op9_hours'                     => ['value' => $result['OP9_TotalHours']],
          'field_op10_amount'                   => ['value' => $result['OP10_TotalAmt']],
          'field_op10_hours'                    => ['value' => $result['OP10_TotalHours']],
          'field_op11_amount'                   => ['value' => $result['OP11_TotalAmt']],
          'field_op11_hours'                    => ['value' => $result['OP11_TotalHours']],
          'field_op15_amount'                   => ['value' => $result['OP15_TotalAmt']],
          'field_op15_hours'                    => ['value' => $result['OP15_TotalHours']],
          'field_op16_amount'                   => ['value' => $result['OP16_TotalAmt']],
          'field_op16_hours'                    => ['value' => $result['OP16_TotalHours']],
          'field_op26_amount'                   => ['value' => $result['OP26_TotalAmt']],
          'field_op26_hours'                    => ['value' => $result['OP26_TotalHours']],
        ]);

        $nodeEstimate->save();
        // save to xml
        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($postdata);
        $time = time();
        $path = realpath(__DIR__.'/../../');
        $domxml->save($path."/xml/estimate{$time}.xml");

        $directory = \Drupal::service('file_system')->realpath("private://private");
        $domxml->save($directory ."estimate{$time}.xml");

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
