<!--{*
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *}-->
 
 
<div id="order" class="contents-main">

<p style="margin: 20px 0 10px">出荷履歴CSVファイルをアップロードして一括出荷完了処理が行えます。</p>
<!--{if $arrError.csv_file}-->
<p class="attention"><!--{$arrError.csv_file}--></p>
<!--{/if}-->
<form name="form1" id="form1" method="post" action="<!--{$smarty.server.REQUEST_URI|escape}-->" enctype="multipart/form-data">
<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
<input type="hidden" name="mode" value="" />


    <table>
        <tr>
            <th>CSVファイル</th>
			<td>
				<input type="file" name="csv_file" value="ファイル選択">
            </td>
			<td>
				<input type="submit" value="アップロード" onclick="fnFormModeSubmit('form1', 'csv_upload', '', ''); return false;">
			</td>
        </tr>
    </table>

<!--{if $arrDoneOrder}-->
<p>以下の発送について発送完了処理を行いました。</p>
<table>
<thead>
<tr>
<th style="width: 30px;">受注ID</th>
<th>発送先名</th>
<th>お問い合わせ番号</th>
<th>発送日</th>
</tr>
</thead>
<tbody>
    <!--{foreach from=$arrDoneOrder item=item name=loop}-->
    <tr>
        <td><!--{$item.order_id|h}--></td>
        <td><!--{$item.shipping_name01|h}--><!--{$item.shipping_name02|h}-->様</td>
        <td><!--{$item.plg_paehidenutills_toiban|h}--></td>
        <td><!--{$item.shipping_commit_date|h}--></td>
    </tr>
    <!--{/foreach}-->
</tbody>
</table>
<!--{/if}-->

<!--{if $arrForm}-->
	<p>内容を確認して「一括処理を行う」ボタンを押してください。    <span class="attention">チェックを外すと完了処理をされません</span></p>

	<table>
		<thead>
			<tr>
				<th style="width: 30px;"><a href="javascirpt:void(0)" id="plg_paehiden_batch">一括</a></th>
                <th style="width: 30px;">受注ID</th>
                <th>発送先名</th>
                <th>お問い合わせ番号</th>
                <th>発送日</th>

<!--{if $plg_paehidenutills_config.mail_flg}-->
                <th>発送完了メール</th>
<!--{/if}-->

			</tr>
		</thead>
		<tbody>
	<!--{foreach from=$arrForm.order_id item=order_id name=loop}-->
		<!--{assign var=index value=$smarty.foreach.loop.index}-->
			<tr>
				<td>
					<input type="checkbox" 
						   name="check[<!--{$index}-->]" 
						   value="1"
						   <!--{if $arrForm.check[$index]}-->checked="checked"<!--{/if}-->
						   />
				</td>
				<td>
					<!--{$order_id|h}-->
					<input type="hidden" 
						   name="order_id[<!--{$index}-->]" 
						   value="<!--{$order_id|h}-->" />
					<input type="hidden" 
						   name="shipping_id[<!--{$index}-->]" 
						   value="<!--{$arrForm.shipping_id[$index]|h}-->" />
				</td>
				<td>
					<!--{$arrForm.shipping_name01[$index]|h}--><!--{$arrForm.shipping_name02[$index]|h}-->様
				</td>
				<td>
                    <!--{if $arrError.plg_paehidenutills_toiban[$index]}-->
                    <p class="attention"><!--{$arrError.plg_paehidenutills_toiban[$index]}--></p>
                    <!--{/if}-->
					<input type="text" 
						   name="plg_paehidenutills_toiban[<!--{$index}-->]" 
						   value="<!--{$arrForm.plg_paehidenutills_toiban[$index]|h}-->"
						   size="20" class="box20" maxlength="12" style="<!--{$arrError.plg_paehidenutills_toiban[$index]|sfGetErrorColor}-->" />
				</td>
				<td style="<!--{$arrError.shipping_commit_year[$index]|sfGetErrorColor}-->">

                <!--{if $arrError.shipping_commit_year[$index]}-->
                    <p class="attention"><!--{$arrError.shipping_commit_year[$index]}--></p>
                <!--{/if}-->

<select name="shipping_commit_year[<!--{$index}-->]">
<option value="">----</option>
<!--{html_options options=$arrYear selected=$arrForm.shipping_commit_year[$index]|default:$year}-->
</select>年

<select name="shipping_commit_month[<!--{$index}-->]">
<option value="">--</option>
<!--{html_options options=$arrMonth selected=$arrForm.shipping_commit_month[$index]|default:$month}-->
</select>月

<select name="shipping_commit_day[<!--{$index}-->]">
<option value="">--</option>
<!--{html_options options=$arrDay selected=$arrForm.shipping_commit_day[$index]|default:$day}-->
</select>日

				</td>

<!--{if $plg_paehidenutills_config.mail_flg}-->

				<td style="<!--{$arrError.mail_template_id[$index]|sfGetErrorColor}-->">
                <!--{if $arrError.mail_template_id[$index]}-->
                    <p class="attention"><!--{$arrError.mail_template_id[$index]}--></p>
                <!--{/if}-->
					<select name="mail_template_id[<!--{$index}-->]">
						<option value="">送信しない</option>
	                    <!--{html_options options=$arrMailTemplate selected=$arrForm.mail_template_id[$index]}-->
					</select>
				</td>

<!--{/if}-->
			</tr>
	
	<!--{/foreach}-->
		</tbody>

	</table>

		
    <div class="btn-area">
        <ul>
            <li><a class="btn-action" href="javascript:;" onclick="if (confirm('一括処理を行ってもよろしいですか？')) fnFormModeSubmit('form1', 'done', '', '');"><span class="btn-next">一括処理を行う</span></a></li>
        </ul>
    </div>
		
		
<!--{/if}-->

</form>

<!--/.contents-main--></div>