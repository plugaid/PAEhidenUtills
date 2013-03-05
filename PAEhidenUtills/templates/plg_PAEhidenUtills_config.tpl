<!--{include file="`$smarty.const.TEMPLATE_ADMIN_REALDIR`admin_popup_header.tpl"}-->

<script type="text/javascript">//<![CDATA[
self.moveTo(20,20);
self.resizeTo(620, 720);
self.focus();
//]]>
</script>
<h2><!--{$tpl_subtitle}--></h2>
<form name="form1" id="form1" method="post" action="<!--{$smarty.server.REQUEST_URI|escape}-->">
<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
<input type="hidden" name="mode" value="" />
<p class="remark">
</p>
<h3>エクスポート設定</h3>
<table class="form">
        <tr>
            <th>受注IDを「お客様管理コード」にエクスポートする</th>
            <td>
                <!--{if $arrErr.order_id_flg}-->
                    <span class="attention"><!--{$arrErr.order_id_flg}--></span><br />
                <!--{/if}-->
                <label>
                <input type="checkbox" name="order_id_flg" value="1" <!--{if $arrForm.order_id_flg == "1"}-->checked<!--{/if}--> />エクスポートする
                </label>
                <br /><span class="attention">出荷履歴データのインポートを利用する場合はチェックしてください</span>
            </td>
        </tr>
        <tr>
            <th>受注IDのプレフィックス</th>
            <td>
                <!--{if $arrErr.order_id_prefix}-->
                    <span class="attention"><!--{$arrErr.order_id_prefix}--></span><br />
                <!--{/if}-->
                <input type="text" name="order_id_prefix" value="<!--{$arrForm.order_id_prefix|h}-->" maxlength="8" style="<!--{if $arrErr.order_id_prefix != ""}-->background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="20" class="box20" />
                <br /><span class="attention"> 必要な場合入力してください(上限4文字)</span>
            </td>
        </tr>
        <tr>
            <th>対応する配送設定<span class="attention">※</span></th>
            <td>
                <!--{if $arrErr.deliv_id}-->
                    <span class="attention"><!--{$arrErr.deliv_id}--></span><br />
                <!--{/if}-->
                <select name="deliv_id[]" style="<!--{$arrErr[$key]|sfGetErrorColor}-->" multiple="multiple" size="8">
                    <!--{html_options options=$arrDeliv selected=$arrForm.deliv_id}-->
                </select>
            </td>
        </tr>
</table>
<h3>インポート設定</h3>
<table class="form">
       <tr>
            <th>出荷履歴データのインポートを利用する</th>
            <td>
                <!--{if $arrErr.import_flg}-->
                    <span class="attention"><!--{$arrErr.import_flg}--></span><br />
                <!--{/if}-->
                <label>
                <input type="checkbox" name="import_flg" value="1" <!--{if $arrForm.import_flg == "1"}-->checked<!--{/if}--> />利用する
                </label>
            </td>
        </tr>
       <tr>
            <th>お問い合わせNo.を保存する</th>
            <td>
                <!--{if $arrErr.toiban_flg}-->
                    <span class="attention"><!--{$arrErr.toiban_flg}--></span><br />
                <!--{/if}-->
                <label>
                <input type="checkbox" name="toiban_flg" value="1" <!--{if $arrForm.toiban_flg == "1"}-->checked<!--{/if}--> />保存する
                </label>
            </td>
        </tr>
    <tr>
        <th>お荷物問い合わせサービスURL</th>
        <td>
        <!--{if $arrErr.toiban_url}-->
            <span class="attention"><!--{$arrErr.toiban_url}--></span><br />
                <!--{/if}-->
            <input type="text" name="toiban_url" value="<!--{$arrForm.toiban_url|h}-->" style="<!--{if $arrErr.toiban_url != ""}-->background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="40" class="box40" />
        </td>
    </tr>
       <tr>
            <th>出荷完了メールを自動で送信する</th>
            <td>
                <!--{if $arrErr.mail_flg}-->
                    <span class="attention"><!--{$arrErr.mail_flg}--></span><br />
                <!--{/if}-->
                <label>
                <input type="checkbox" name="mail_flg" value="1" <!--{if $arrForm.mail_flg == "1"}-->checked<!--{/if}--> />送信する
                </label>
            </td>
        </tr>
        <tr>
            <th>出荷完了メールメールテンプレート</th>
            <td>
                <!--{if $arrErr.mail_template_id}-->
                    <span class="attention"><!--{$arrErr.mail_template_id}--></span><br />
                <!--{/if}-->
                    <select name="mail_template_id" style="<!--{$arrErr.mail_template_id|sfGetErrorColor}-->">
                        <!--{html_options options=$arrMailTemplate selected=$arrForm.mail_template_id}-->
                    </select>

            </td>
        </tr>
</table>
<div class="btn-area">
  <ul>
  <li><a class="btn-action" href="javascript:;" onclick="fnFormModeSubmit('form1', '', '', ''); return false;"><span class="btn-next"><!--{if $tpl_submit != ""}--><!--{$tpl_submit}--><!--{else}-->変更<!--{/if}--></span></a></li>
  </ul>
</div>
</form>

<script type="text/javascript">
$(function(){
    var $form = $('#form1');
    
    $form.find('input[name=import_flg]').change(function(){
        if ($(this).attr('checked')) {
            $form.find('input[name=order_id_flg]').attr('checked', 'checked');
        }
    });
});

</script>

<!--{include file="`$smarty.const.TEMPLATE_ADMIN_REALDIR`admin_popup_footer.tpl"}-->
