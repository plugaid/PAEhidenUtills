<?php
/*
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */
// {{{ requires
require_once CLASS_EX_REALDIR . 'page_extends/admin/LC_Page_Admin_Ex.php';

/**
 * インポートページ
 *
 * @package PAEhidenUtills
 * @author PlugAid inc.
 * @version $Id$
 */
class LC_Page_Plugin_PAEhidenUtills_Admin_Order_Import extends LC_Page_Admin_Ex {

    var $plg_PAEhidenUtills_config = array();

    // }}}
    // {{{ functions

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();

        $this->plg_PAEhidenUtills_config = PAEhidenUtills::loadConfig();

        // インポートに対応していない場合は受注トップに飛ばす
        if (empty($this->plg_PAEhidenUtills_config['import_flg'])) {
            SC_Response_Ex::sendRedirect(HTTP_URL . 'admin/order/');
            eixt();
        }

        $this->tpl_mainpage = PLUGIN_UPLOAD_REALDIR . 'PAEhidenUtills/templates/admin/order/plg_PAEhidenUtills_import.tpl';
        $this->tpl_mainno = 'order';
        $this->tpl_subno = 'index';
        $this->tpl_pager = 'pager.tpl';
        $this->tpl_maintitle = '受注管理';
        $this->tpl_subtitle = 'e飛伝II 出荷履歴データのインポート';

        $masterData = new SC_DB_MasterData_Ex();
        $this->arrMailTemplate = $masterData->getMasterData('mtb_mail_template');

        $objDate = new SC_Date_Ex();
        // 登録・更新日検索用
        $objDate->setStartYear(RELEASE_YEAR);
        $objDate->setEndYear(DATE('Y'));
        $this->arrYear = $objDate->getYear();
        // 月日の設定
        $this->arrMonth = $objDate->getMonth();
        $this->arrDay = $objDate->getDay();


        $this->arrError = array();
        $this->httpCacheControl('nocache');
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    function process() {
        $this->action();
        $this->sendResponse();
    }

	
	/**
	 * Page のアクション.
	 */
	function action() {
        switch($this->getMode()) {
		case 'csv_upload':
			$result = $this->doUploadCsv();
			if ($result && empty ($this->arrError)) {
				$this->setCsvData($result);
			}
			break;
        case 'done':
	        $objFormParam = new SC_FormParam_Ex();
		    $this->lfInitParam($objFormParam);
			$objFormParam->setParam($_POST);
            $objFormParam->convParam();
            $objFormParam->trimParam();
            $this->arrError = $this->lfCheckError($objFormParam);
            $this->arrForm = $objFormParam->getHashArray();



            $arrRes = $this->loadOrder($this->arrForm['order_id']);
            if ($arrRes) {
                $arrOrder = array();
                foreach ($arrRes as $res) {
                    $arrOrder[$res['order_id']][$res['shipping_id']] = $res;
                }
                // データをセット
                foreach ($this->arrForm['order_id'] as $index => $order_id) {
                    $shipping_id = $this->arrForm['shipping_id'][$index];
                    if (isset($arrOrder[$order_id][$shipping_id])) {
                        $data = $arrOrder[$order_id][$shipping_id];
                        $this->arrForm['shipping_name01'][$index] = $data['shipping_name01'];
                        $this->arrForm['shipping_name02'][$index] = $data['shipping_name02'];
                        $this->arrForm['shipping_commit_date'][$index] = sprintf('%d-%02d-%02d',
                            $this->arrForm['shipping_commit_year'][$index], $this->arrForm['shipping_commit_month'][$index], $this->arrForm['shipping_commit_day'][$index]);
                    } else {
//                        // 消去
//                        foreach (array_keys($this->arrForm) as $key) {
//                            unset($this->arrForm[$key][$index]);
//                        }
                    }
                }

            }

            if ($this->arrError) {
            } else {
                $this->doneImport();
            }

			break;


        }

	}


    /**
     * デストラクタ.
     *
     * @return void
     */
    function destroy() {
        parent::destroy();
    }



    /**
     * CSVアップロードを実行します.
     *
     * @return void
     */
    function doUploadCsv() {
        // CSVファイルアップロード情報の初期化
        $objUpFile = new SC_UploadFile_Ex(IMAGE_TEMP_REALDIR, IMAGE_SAVE_REALDIR);
		$objUpFile->addFile("CSVファイル", 'csv_file', array('csv'), CSV_SIZE, true, 0, 0, false);


        // ファイルアップロードのチェック
        $objUpFile->makeTempFile('csv_file');
        $arrErr = $objUpFile->checkExists();
        if (count($arrErr) > 0) {
            $this->arrError = $arrErr;
            return;
        }
        // 一時ファイル名の取得
        $filepath = $objUpFile->getTempFilePath('csv_file');
        // CSVファイルの文字コード変換
        $enc_filepath = SC_Utils_Ex::sfEncodeFile($filepath, CHAR_CODE, CSV_TEMP_REALDIR);
        // CSVファイルのオープン
        $fp = fopen($enc_filepath, 'r');
        // 失敗した場合はエラー表示
        if (!$fp) {
             SC_Utils_Ex::sfDispError("アップロードファイルのオープンに失敗しました。");
        }

        // 行数
        $line_count = 0;
		$result = array();
        while (!feof($fp)) {
            $result[] = $this->_fgetcsv_reg($fp);
            // 行カウント
            $line_count++;
        }

        fclose($fp);
        return $result;
    }
	
    private function _fgetcsv_reg (&$handle, $length = null, $d = ',', $e = '"') {
        $_line = "";
        $eof = false;
        while ($eof != true) {

            $_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));

            $itemcnt = preg_match_all('/'.$e.'/', $_line, $dummy);

            if ($itemcnt % 2 == 0) $eof = true;

        }
        return $this->_getcsv_reg($_line, $length, $d, $e);
    }


    private function _getcsv_reg ($line, $length = null, $d = ',', $e = '"') {
        $d = preg_quote($d);
        $e = preg_quote($e);
        $_line = $line;

        $_csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($_line));
        $_csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
        preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
        $_csv_data = $_csv_matches[1];

        for($_csv_i=0;$_csv_i<count($_csv_data);$_csv_i++){
            $_csv_data[$_csv_i]=preg_replace('/^'.$e.'(.*)'.$e.'$/s','$1',$_csv_data[$_csv_i]);
            $_csv_data[$_csv_i]=str_replace($e.$e, $e, $_csv_data[$_csv_i]);
        }
        return empty($_line) ? false : $_csv_data;
    }

	
	
	
	function setCsvData($result) {
		$config = PAEhidenUtills::loadConfig();
		
		$search = array();
		$arrID = array();
		$this->arrForm = array();
		// CSVのデータをチェック
		for ($i = 1; $i < count($result); $i ++) {
			$data = $result[$i];

			// 問い合わせ番号
			$toiban = trim($data[0]);
			// 発送日
			$date = trim($data[1]);
			$time = 0;
			if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $matches)) {
				$time = strtotime("{$matches[1]}/{$matches[2]}/{$matches[3]}");
			}
			// 受注ID,送付ID
			$number = mb_convert_kana($data[12], 'a');
			if (empty($number)) {
				continue;
			}
			if (!empty($config['order_id_prefix'])) {
				if (!preg_match('/^' . $config['order_id_prefix'] . '/', $number)) {
					continue;
				}
				$number = preg_replace('/^' . $config['order_id_prefix'] . '/', '', $number);
			}
			$tmp = @explode('_', $number, 2);
			if (!preg_match('/^\d+$/', $tmp[0]) || !preg_match('/^\d+$/', $tmp[1])) {
				continue;
			}
			$order_id = (int)$tmp[0];
			$shipping_id = (int)$tmp[1];
			
			$search[$order_id][$shipping_id] = array(
				'toiban' => $toiban,
				'year' => date('Y', $time),
				'month' => date('m', $time),
				'day' => date('d', $time),
			);
			$arrID[] = (int)$order_id;
		}
		if (!$arrID) {
			return;
		}

        $arrRes = $this->loadOrder($arrID);
		
		$index = 0;
		foreach ($arrRes as $res) {
			if (empty($search[$res['order_id']][$res['shipping_id']])) {
				continue;
			}
			$data = $search[$res['order_id']][$res['shipping_id']];
			$this->arrForm['order_id'][$index] = $res['order_id'];
			$this->arrForm['shipping_id'][$index] = $res['shipping_id'];
			$this->arrForm['shipping_name01'][$index] = $res['shipping_name01'];
			$this->arrForm['shipping_name02'][$index] = $res['shipping_name02'];
			$this->arrForm['plg_paehidenutills_toiban'][$index] = $data['toiban'];
			$this->arrForm['shipping_commit_year'][$index] = $data['year'];
			$this->arrForm['shipping_commit_month'][$index] = $data['month'];
			$this->arrForm['shipping_commit_day'][$index] = $data['day'];
			$this->arrForm['mail_template_id'][$index] = $config['mail_template_id'];
			$this->arrForm['check'][$index] = 1;
			$index ++;
		}
	}



    function doneImport() {
        // 制限時間を無制限に
        set_time_limit(0);

        $arrOrder = SC_Utils_Ex::sfSwapArray($this->arrForm);
        $this->arrForm = array();

        $arrDone = array();
        $arrNone = array();

        if (!$arrOrder) {
            return;
        }

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objQuery->begin();

        // 全発送完了チェック用の条件
        $countWhere = 'order_id=? AND del_flg=0 AND (shipping_commit_date IS NULL)';

        foreach ($arrOrder as $order) {
            // チェックされてなければとばす
            if (empty($order['check'])) {
                $arrNone[] = $order;
                continue;
            }
            $arrVal = array(
                'shipping_commit_date' => sprintf('%d-%02d-%02d',
                    $order['shipping_commit_year'], $order['shipping_commit_month'], $order['shipping_commit_day']),
                'update_date' => 'CURRENT_TIMESTAMP',
            );
            // お問い合わせ番号も保存
            if ($this->plg_PAEhidenUtills_config['toiban_flg']) {
                $arrVal['plg_paehidenutills_toiban'] = $order['plg_paehidenutills_toiban'];
            }

            $objQuery->update('dtb_shipping', $arrVal, 'order_id=? AND shipping_id=?',
                array($order['order_id'], $order['shipping_id']));

            // メール発送処理
            if ($this->plg_PAEhidenUtills_config['mail_flg']) {
                $this->sendDoneMail($order);
            }

            // すべての発送が完了しているか確認
            $cnt = $objQuery->count('dtb_shipping', $countWhere, array($order['order_id']));
            // すべて完了してたらオーダーステータスをあげる
            if (!$cnt) {

                $sqlval = array();
                $sqlval['commit_date'] = 'CURRENT_TIMESTAMP';
                $sqlval['status'] = ORDER_DELIV;
                $sqlval['update_date'] = 'CURRENT_TIMESTAMP';

                $objQuery->update('dtb_order', $sqlval, 'order_id=?', array($order['order_id']));

            }
            $arrDone[] = $order;
        }


        $objQuery->commit();

        if ($arrNone) {
            $this->arrForm = SC_Utils_Ex::sfSwapArray($arrNone);
        }
        // 終了
        $this->arrDoneOrder = $arrDone;

    }



    function sendDoneMail($order) {
        $template_id = $this->plg_PAEhidenUtills_config['mail_template_id'];
        $order_id = $order['order_id'];
        $shipping_id = $order['shipping_id'];

        $arrTplVar = new stdClass();

        $arrTplVar->plg_PAEhidenUtills_config = $this->plg_PAEhidenUtills_config;

        $arrInfo = SC_Helper_DB_Ex::sfGetBasisData();
        $arrTplVar->arrInfo = $arrInfo;

        $objQuery =& SC_Query_Ex::getSingletonInstance();

        $mailHelper = new SC_Helper_Mail_Ex();


        // メールテンプレート情報の取得
        $where = 'template_id = ?';
        $arrRet = $objQuery->select('subject, header, footer', 'dtb_mailtemplate', $where, array($template_id));
        $arrTplVar->tpl_header = $arrRet[0]['header'];
        $arrTplVar->tpl_footer = $arrRet[0]['footer'];
        $tmp_subject = $arrRet[0]['subject'];

        // 受注情報の取得
        $where = 'order_id = ? AND del_flg = 0';
        $arrOrder = $objQuery->getRow('*', 'dtb_order', $where, array($order_id));

        if (empty($arrOrder)) {
            trigger_error("該当する受注が存在しない。(注文番号: $order_id)", E_USER_ERROR);
        }

        $where = 'order_id = ?';
        $objQuery->setOrder('order_detail_id');
        $arrTplVar->arrOrderDetail = $objQuery->select('*', 'dtb_order_detail', $where, array($order_id));

        $objProduct = new SC_Product_Ex();
        $objQuery->setOrder('shipping_id');
        $arrRet = $objQuery->select('*', 'dtb_shipping', 'order_id = ? AND shipping_id = ?', array($order_id, $shipping_id));
        if (empty($arrRet[0])) {
            trigger_error("該当する発送先が存在しない。(注文番号: $order_id 発送ID: $shipping_id)", E_USER_ERROR);
        }

        $objQuery->setOrder('shipping_id');
        $arrItems = $objQuery->select('*', 'dtb_shipment_item', 'order_id = ? AND shipping_id = ?',
                array($order_id, $arrRet[0]['shipping_id']));
        foreach ($arrItems as $arrDetail) {
            foreach ($arrDetail as $detailKey => $detailVal) {
                $arrRet[0]['shipment_item'][$arrDetail['product_class_id']][$detailKey] = $detailVal;
            }

            $arrRet[0]['shipment_item'][$arrDetail['product_class_id']]['productsClass'] =& $objProduct->getDetailAndProductsClass($arrDetail['product_class_id']);
        }
        $arrTplVar->shipping = $arrRet[0];

        $arrTplVar->Message_tmp = $arrOrder['message'];


        // 会員情報の取得
        $customer_id = $arrOrder['customer_id'];
        $objQuery->setOrder('customer_id');
        $arrRet = $objQuery->select('point', 'dtb_customer', 'customer_id = ?', array($customer_id));
        $arrCustomer = isset($arrRet[0]) ? $arrRet[0] : '';

        $arrTplVar->arrCustomer = $arrCustomer;
        $arrTplVar->arrOrder = $arrOrder;

        //その他決済情報
        if ($arrOrder['memo02'] != '') {
            $arrOther = unserialize($arrOrder['memo02']);

            foreach ($arrOther as $other_key => $other_val) {
                if (SC_Utils_Ex::sfTrim($other_val['value']) == '') {
                    $arrOther[$other_key]['value'] = '';
                }
            }

            $arrTplVar->arrOther = $arrOther;
        }

        // 都道府県変換
        $arrTplVar->arrPref = $this->arrPref;

        $objCustomer = new SC_Customer_Ex();
        $arrTplVar->tpl_user_point = $objCustomer->getValue('point');

        $objMailView = new SC_SiteView_Ex();

        // メール本文の取得
        $template_path = 'file:' . realpath(PLUGIN_UPLOAD_REALDIR . 'PAEhidenUtills/templates/default/mail_templates/plg_paehidenutills_order_mail.tpl');

        $objMailView->setPage($this);
        $objMailView->assignobj($arrTplVar);
        $body = $objMailView->fetch($template_path);

        // メール送信処理
        $objSendMail = new SC_SendMail_Ex();
        $bcc = $arrInfo['email01'];
        $from = $arrInfo['email03'];
        $error = $arrInfo['email04'];


        $tosubject = $mailHelper->sfMakeSubject($tmp_subject, $objMailView);

        $objSendMail->setItem('', $tosubject, $body, $from, $arrInfo['shop_name'], $from, $error, $error, $bcc);
        $objSendMail->setTo($arrOrder['order_email'], $arrOrder['order_name01'] . ' '. $arrOrder['order_name02'] .' 様');

        // 送信フラグ:trueの場合は、送信する。
        if ($objSendMail->sendMail()) {
            $mailHelper->sfSaveMailHistory($order_id, $template_id, $tosubject, $body);
        }

    }




    /**
     * 受注情報を返す
     *
     * @param $arrID
     * @return array
     */
    function loadOrder($arrID) {
        if (empty($arrID)) {
            return;
        }
        $arrID = array_map('intval', $arrID);
        $objQuery = &SC_Query_Ex::getSingletonInstance();
        $arrRes = $objQuery->getAll('SELECT'
                . ' *'
                . ' FROM dtb_order'
                . ' LEFT JOIN dtb_shipping USING(order_id)'
                . ' WHERE dtb_order.order_id IN (' . implode(',', $arrID) . ')'
                . ' AND dtb_order.del_flg=0'
                . ' AND dtb_shipping.del_flg=0'
        );
        return $arrRes;
    }


    /**
     * パラメーター情報の初期化を行う.
     *
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @return void
     */
    function lfInitParam(&$objFormParam) {
        $objFormParam->addParam('受注ID', 'order_id', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
        $objFormParam->addParam('発送ID', 'shipping_id', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
        // 発送日
        $objFormParam->addParam('発送日年', 'shipping_commit_year', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
        $objFormParam->addParam('発送日月', 'shipping_commit_month', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
        $objFormParam->addParam('発送日日', 'shipping_commit_day', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));

        $objFormParam->addParam('お問い合わせ番号', 'plg_paehidenutills_toiban', 12, 'n', array('MAX_LENGTH_CHECK', 'ALNUM_CHECK'));
		
        $objFormParam->addParam('メール設定', 'mail_template_id', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));

        $objFormParam->addParam('チェック', 'check', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));

    }

    /**
     * 入力内容のチェックを行う.
     *
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @return void
     */
    function lfCheckError(&$objFormParam) {
		$param = $objFormParam->getHashArray();
		
        $objErr = new SC_CheckError_Ex($param);
        $objErr->arrErr = $objFormParam->checkError();
		
        // 発送日
        if (empty($objErr->arrErr['shipping_commit_year'])) {
			foreach ($param['shipping_commit_year'] as $index => $year) {
				$month = $param['shipping_commit_month'][$index];
				$day = $param['shipping_commit_day'][$index];
				if (!$year && !$month && !$day) {
					continue;
				}
				if (!$year || !$month || !$day) {
					$objErr->arrErr['shipping_commit_year'][$index] = '※ 発送日はすべての項目を入力して下さい。<br />';
				} else if (!checkdate($month, $day, $year)) {
					$objErr->arrErr['shipping_commit_year'][$index] = '※ 発送日が正しくありません。<br />';
				}
			}
        }

        return $objErr->arrErr;
    }


}
