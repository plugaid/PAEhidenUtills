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
                    } else {
//                        // 消去
//                        foreach (array_keys($this->arrForm) as $key) {
//                            unset($this->arrForm[$key][$index]);
//                        }
                    }
                }

            }

            if ($this->arrError) {
                pr($this->arrError);
            } else {
                $this->doneImport();
            }

			break;


        }

//		$this->searchOrder();
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

        $objFormParam->addParam('お問い合わせ番号', 'plg_paehidenutills_toiban', 2, 'n', array('MAX_LENGTH_CHECK', 'ALNUM_CHECK'));
		
        $objFormParam->addParam('メール設定', 'mail_template_id', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));

        $objFormParam->addParam('チェック', 'check', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));

//        $objFormParam->addParam('対応状況', 'search_order_status', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('注文者 お名前', 'search_order_name', STEXT_LEN, 'KVa', array('MAX_LENGTH_CHECK'));
//        $objFormParam->addParam('注文者 お名前(フリガナ)', 'search_order_kana', STEXT_LEN, 'KVCa', array('KANA_CHECK','MAX_LENGTH_CHECK'));
//        $objFormParam->addParam('性別', 'search_order_sex', INT_LEN, 'n', array('MAX_LENGTH_CHECK'));
//        $objFormParam->addParam('年齢1', 'search_age1', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('年齢2', 'search_age2', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('メールアドレス', 'search_order_email', STEXT_LEN, 'KVa', array('MAX_LENGTH_CHECK'));
//        $objFormParam->addParam('TEL', 'search_order_tel', STEXT_LEN, 'KVa', array('MAX_LENGTH_CHECK'));
//        $objFormParam->addParam('支払い方法', 'search_payment_id', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('購入金額1', 'search_total1', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('購入金額2', 'search_total2', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('表示件数', 'search_page_max', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了年', 'search_eorderyear', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了月', 'search_eordermonth', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了日', 'search_eorderday', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        // 更新日
//        $objFormParam->addParam('開始年', 'search_supdateyear', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('開始月', 'search_supdatemonth', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('開始日', 'search_supdateday', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了年', 'search_eupdateyear', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了月', 'search_eupdatemonth', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了日', 'search_eupdateday', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        // 生年月日
//        $objFormParam->addParam('開始年', 'search_sbirthyear', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('開始月', 'search_sbirthmonth', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('開始日', 'search_sbirthday', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了年', 'search_ebirthyear', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了月', 'search_ebirthmonth', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('終了日', 'search_ebirthday', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
//        $objFormParam->addParam('購入商品','search_product_name',STEXT_LEN,'KVa',array('MAX_LENGTH_CHECK'));
//        $objFormParam->addParam('ページ送り番号','search_pageno', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
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
		
		
//        // 相関チェック
//        $objErr->doFunc(array('注文番号1', '注文番号2', 'search_order_id1', 'search_order_id2'), array('GREATER_CHECK'));
//        $objErr->doFunc(array('年齢1', '年齢2', 'search_age1', 'search_age2'), array('GREATER_CHECK'));
//        $objErr->doFunc(array('購入金額1', '購入金額2', 'search_total1', 'search_total2'), array('GREATER_CHECK'));
//        // 受注日
//        $objErr->doFunc(array('開始', 'search_sorderyear', 'search_sordermonth', 'search_sorderday'), array('CHECK_DATE'));
//        $objErr->doFunc(array('終了', 'search_eorderyear', 'search_eordermonth', 'search_eorderday'), array('CHECK_DATE'));
//        $objErr->doFunc(array('開始', '終了', 'search_sorderyear', 'search_sordermonth', 'search_sorderday', 'search_eorderyear', 'search_eordermonth', 'search_eorderday'), array('CHECK_SET_TERM'));
//        // 更新日
//        $objErr->doFunc(array('開始', 'search_supdateyear', 'search_supdatemonth', 'search_supdateday'), array('CHECK_DATE'));
//        $objErr->doFunc(array('終了', 'search_eupdateyear', 'search_eupdatemonth', 'search_eupdateday'), array('CHECK_DATE'));
//        $objErr->doFunc(array('開始', '終了', 'search_supdateyear', 'search_supdatemonth', 'search_supdateday', 'search_eupdateyear', 'search_eupdatemonth', 'search_eupdateday'), array('CHECK_SET_TERM'));
//        // 生年月日
//        $objErr->doFunc(array('開始', 'search_sbirthyear', 'search_sbirthmonth', 'search_sbirthday'), array('CHECK_DATE'));
//        $objErr->doFunc(array('終了', 'search_ebirthyear', 'search_ebirthmonth', 'search_ebirthday'), array('CHECK_DATE'));
//        $objErr->doFunc(array('開始', '終了', 'search_sbirthyear', 'search_sbirthmonth', 'search_sbirthday', 'search_ebirthyear', 'search_ebirthmonth', 'search_ebirthday'), array('CHECK_SET_TERM'));

		
        return $objErr->arrErr;
    }

    /**
     * クエリを構築する.
     *
     * 検索条件のキーに応じた WHERE 句と, クエリパラメーターを構築する.
     * クエリパラメーターは, SC_FormParam の入力値から取得する.
     *
     * 構築内容は, 引数の $where 及び $arrValues にそれぞれ追加される.
     *
     * @param string $key 検索条件のキー
     * @param string $where 構築する WHERE 句
     * @param array $arrValues 構築するクエリパラメーター
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @return void
     */
    function buildQuery($key, &$where, &$arrValues, &$objFormParam) {
        $dbFactory = SC_DB_DBFactory_Ex::getInstance();
        switch ($key) {

            case 'search_product_name':
                $where .= ' AND EXISTS (SELECT 1 FROM dtb_order_detail od WHERE od.order_id = dtb_order.order_id AND od.product_name LIKE ?)';
                $arrValues[] = sprintf('%%%s%%', $objFormParam->getValue($key));
                break;
            case 'search_order_name':
                $where .= ' AND ' . $dbFactory->concatColumn(array('order_name01', 'order_name02')) . ' LIKE ?';
                $arrValues[] = sprintf('%%%s%%', $objFormParam->getValue($key));
                break;
            case 'search_order_kana':
                $where .= ' AND ' . $dbFactory->concatColumn(array('order_kana01', 'order_kana02')) . ' LIKE ?';
                $arrValues[] = sprintf('%%%s%%', $objFormParam->getValue($key));
                break;
            case 'search_order_id1':
                $where .= ' AND order_id >= ?';
                $arrValues[] = sprintf('%d', $objFormParam->getValue($key));
                break;
            case 'search_order_id2':
                $where .= ' AND order_id <= ?';
                $arrValues[] = sprintf('%d', $objFormParam->getValue($key));
                break;
            case 'search_order_sex':
                $tmp_where = '';
                foreach ($objFormParam->getValue($key) as $element) {
                    if ($element != '') {
                        if (SC_Utils_Ex::isBlank($tmp_where)) {
                            $tmp_where .= ' AND (order_sex = ?';
                        } else {
                            $tmp_where .= ' OR order_sex = ?';
                        }
                        $arrValues[] = $element;
                    }
                }

                if (!SC_Utils_Ex::isBlank($tmp_where)) {
                    $tmp_where .= ')';
                    $where .= " $tmp_where ";
                }
                break;
            case 'search_order_tel':
                $where .= ' AND (' . $dbFactory->concatColumn(array('order_tel01', 'order_tel02', 'order_tel03')) . ' LIKE ?)';
                $arrValues[] = sprintf('%%%d%%', preg_replace('/[()-]+/','', $objFormParam->getValue($key)));
                break;
            case 'search_order_email':
                $where .= ' AND order_email LIKE ?';
                $arrValues[] = sprintf('%%%s%%', $objFormParam->getValue($key));
                break;
            case 'search_payment_id':
                $tmp_where = '';
                foreach ($objFormParam->getValue($key) as $element) {
                    if ($element != '') {
                        if ($tmp_where == '') {
                            $tmp_where .= ' AND (payment_id = ?';
                        } else {
                            $tmp_where .= ' OR payment_id = ?';
                        }
                        $arrValues[] = $element;
                    }
                }

                if (!SC_Utils_Ex::isBlank($tmp_where)) {
                    $tmp_where .= ')';
                    $where .= " $tmp_where ";
                }
                break;
            case 'search_total1':
                $where .= ' AND total >= ?';
                $arrValues[] = sprintf('%d', $objFormParam->getValue($key));
                break;
            case 'search_total2':
                $where .= ' AND total <= ?';
                $arrValues[] = sprintf('%d', $objFormParam->getValue($key));
                break;
            case 'search_sorderyear':
                $date = SC_Utils_Ex::sfGetTimestamp($objFormParam->getValue('search_sorderyear'),
                                                    $objFormParam->getValue('search_sordermonth'),
                                                    $objFormParam->getValue('search_sorderday'));
                $where.= ' AND create_date >= ?';
                $arrValues[] = $date;
                break;
            case 'search_eorderyear':
                $date = SC_Utils_Ex::sfGetTimestamp($objFormParam->getValue('search_eorderyear'),
                                                    $objFormParam->getValue('search_eordermonth'),
                                                    $objFormParam->getValue('search_eorderday'), true);
                $where.= ' AND create_date <= ?';
                $arrValues[] = $date;
                break;
            case 'search_supdateyear':
                $date = SC_Utils_Ex::sfGetTimestamp($objFormParam->getValue('search_supdateyear'),
                                                    $objFormParam->getValue('search_supdatemonth'),
                                                    $objFormParam->getValue('search_supdateday'));
                $where.= ' AND update_date >= ?';
                $arrValues[] = $date;
                break;
            case 'search_eupdateyear':
                $date = SC_Utils_Ex::sfGetTimestamp($objFormParam->getValue('search_eupdateyear'),
                                                    $objFormParam->getValue('search_eupdatemonth'),
                                                    $objFormParam->getValue('search_eupdateday'), true);
                $where.= ' AND update_date <= ?';
                $arrValues[] = $date;
                break;
            case 'search_sbirthyear':
                $date = SC_Utils_Ex::sfGetTimestamp($objFormParam->getValue('search_sbirthyear'),
                                                    $objFormParam->getValue('search_sbirthmonth'),
                                                    $objFormParam->getValue('search_sbirthday'));
                $where.= ' AND order_birth >= ?';
                $arrValues[] = $date;
                break;
            case 'search_ebirthyear':
                $date = SC_Utils_Ex::sfGetTimestamp($objFormParam->getValue('search_ebirthyear'),
                                                    $objFormParam->getValue('search_ebirthmonth'),
                                                    $objFormParam->getValue('search_ebirthday'), true);
                $where.= ' AND order_birth <= ?';
                $arrValues[] = $date;
                break;
            case 'search_order_status':
                $where.= ' AND status = ?';
                $arrValues[] = $objFormParam->getValue($key);
                break;
            default:
                break;
        }
    }

    /**
     * 受注を削除する.
     *
     * @param string $where 削除対象の WHERE 句
     * @param array $arrParam 削除対象の値
     * @return void
     */
    function doDelete($where, $arrParam = array()) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $sqlval['del_flg']     = 1;
        $sqlval['update_date'] = 'CURRENT_TIMESTAMP';
        $objQuery->update('dtb_order', $sqlval, $where, $arrParam);
    }

    /**
     * CSV データを構築して取得する.
     *
     * 構築に成功した場合は, ファイル名と出力内容を配列で返す.
     * 構築に失敗した場合は, false を返す.
     *
     * @param string $where 検索条件の WHERE 句
     * @param array $arrVal 検索条件のパラメーター
     * @param string $order 検索結果の並び順
     * @return void
     */
    function doOutputCSV($where, $arrVal, $order) {
        if ($where != '') {
            $where = " WHERE $where ";
        }

        $objCSV = new SC_Helper_CSV_Ex();
        $objCSV->sfDownloadCsv('3', $where, $arrVal, $order, true);
    }

    /**
     * 検索結果の行数を取得する.
     *
     * @param string $where 検索条件の WHERE 句
     * @param array $arrValues 検索条件のパラメーター
     * @return integer 検索結果の行数
     */
    function getNumberOfLines($where, $arrValues) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        return $objQuery->count('dtb_order', $where, $arrValues);
    }

    /**
     * 受注を検索する.
     *
     * @param string $where 検索条件の WHERE 句
     * @param array $arrValues 検索条件のパラメーター
     * @param integer $limit 表示件数
     * @param integer $offset 開始件数
     * @param string $order 検索結果の並び順
     * @return array 受注の検索結果
     */
    function findOrders($where, $arrValues, $limit, $offset, $order) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objQuery->setLimitOffset($limit, $offset);
        $objQuery->setOrder($order);
        return $objQuery->select('*', 'dtb_order', $where, $arrValues);
    }

}
