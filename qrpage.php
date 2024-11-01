<script type="text/javascript">
function CheckStatus(e)
{
	e.preventDefault();
	jQuery.ajax({
	  type:"get",
	  url:"<?php echo esc_url_raw(get_site_url()); ?>?wc-api=netzmeqr_gateway&action=checkstatus&key=<?php echo urlencode($this->encrypt($order_id)); ?>",
	  success:function(data)
	  {
	  		console.log(data);
		  if (data == '1') {
			<?php
			echo "t1 = window.setTimeout(function(){ window.location = '".esc_url_raw($order->get_checkout_order_received_url())."'; },3);";            
			?>
		  } else {
			window.location.href=window.location.href;
		  }
	  }
	});
	return false;
}
</script>
<div class="content-qr" style="margin-top:49px;">
	<div class="time-limit-container" style="margin-left:20px;">
		<div class="time-limit">Bayar sebelum <?php echo esc_html(gmdate('d/m/Y H:i', mktime($datenzexpiredTs['hour'], esc_html($datenzexpiredTs['minute']), esc_html($datenzexpiredTs['second']), esc_html($datenzexpiredTs['month']), esc_html($datenzexpiredTs['day']), esc_html($datenzexpiredTs['year']))));  ?></div> <!--  Minggu, 19 Juni 2022 - 14:01 WIB ?> -->
	</div>
	<div class="body">
		<div class="invoice-info-container">
			<div class="info">
				<div class="label">Jumlah Transfer</div>
				<div class="value"><?php echo "Rp " . number_format($order->get_total(), 0, ",", "."); ?></div>
			</div>
		</div>        
		<div class="qris-image-container" style="text-align: center !important;">
			<div class="image-container">
			<img class="qris-netzme-logo" width="400" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/logo_qris.png"><br/>
		</div>
		<div class="image-container">
			<img src="<?php echo esc_html($qrImage); ?>" width="400" height="400"/>
		</div>
			<div class="label">Terminal Id: <b><?php echo esc_html($nzterminalId); ?></b></div>
		</div>
		<div class="action-container" style="text-align: center;">
			<button id="btn-pay" class="btn primary">Bayar</button>
			<button id="btn-downloads" class="btn primary" onclick="downloadBase64File('<?php echo esc_html($qrImage); ?>', 'image/png', 'qr.png');">Simpan QR</button>
		</div>
		<div class="how-to">
			<div class="title small">Cara membayar dengan QRIS</div>
			<ol>
				<li>Simpan gambar QR di atas ke galeri kamu.</li>
				<li>Pilih aplikasi pembayaran yang ingin kamu pakai.</li>
				<li>Buka halaman bayar dengan QR.</li>
				<li>Pilih icon galeri/upload QR.</li>
				<li>Pilih gambar QR yang telah disimpan di galeri handphone kamu.</li>
				<li>Jika kamu sudah melakukan pembayaran namun halaman ini tidak berubah, silakan cek status pembayaran melalui tombol dibawah ini.</li>
			</ol>
		</div>
		<div class="action-container" style="margin-bottom:15px;">
			<button id="btn-status1" type="button" class="btn primary outline block" onclick="javascript: CheckStatus(event)">CEK STATUS PEMBAYARAN</button>
		</div>
	</div>
	<div class="footer-qr"></div>
</div>
<div class="popup-overlay">
	<div class="share-dialog">
		<div class="share-container">
			<div class="popup-title title-container">
				<div class="title">Pilih aplikasi untuk scan QR</div>
				<button class="btn-close" close-popup-action="" title="Close"></button>
			</div>
			<div class="popup-body">
				<div class="description">Pastikan kamu sudah <b>Simpan QR</b> ke dalam Gallery dan saldo kamu di aplikasi terpilih cukup untuk membayar.</div>
				<div class="description">Buka Menu Bayar di aplikasi terpilih dan <b>Upload QR</b> yang sudah kamu simpan sebelumnya.</div>
				<div class="app-list">
						<a close-popup-action="" href="https://netzme.com/" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_netzme.png" alt="Netzme" class="image-container">
							<span class="app-name">Netzme</span>
						</a>
						<a close-popup-action="" href="https://www.ovo.id/" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_ovo.png" alt="OVO" class="image-container">
							<span class="app-name">OVO</span>
						</a>
						<a close-popup-action="" href="https://www.dana.id/" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_dana.png" alt="Dana" class="image-container">
							<span class="app-name">Dana</span>
						</a>
						<a close-popup-action="" href="https://www.linkaja.id/" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_linkaja.png" alt="LinkAja" class="image-container">
							<span class="app-name">LinkAja</span>
						</a>
						<a close-popup-action="" href="https://www.gojek.com/gopay/" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_gopay.png" alt="Gopay" class="image-container">
							<span class="app-name">Gopay</span>
						</a>
						<a close-popup-action="" href="https://shopee.co.id/" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_shopee.png" alt="Shopee" class="image-container">
							<span class="app-name">Shopee</span>
						</a>
						<a close-popup-action="" href="https://www.bca.co.id/bcamobile" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_bca.png" alt="BCA" class="image-container">
							<span class="app-name">BCA</span>
						</a>
						<a close-popup-action="" href="https://www.jenius.com/" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_jenius.png" alt="Jenius" class="image-container">
							<span class="app-name">Jenius</span>
						</a>
						<a close-popup-action="" href="https://promo.bri.co.id/main/content/main/bri_mobile" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_brimo.png" alt="BRImo" class="image-container">
							<span class="app-name">BRImo</span>
						</a>
						<a close-popup-action="" href="https://www.bankmandiri.co.id/web/guest/mandiri-online" class="app-item" target="_blank">
							<img loading="lazy" src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>assets/images/icon_mandiri.png" alt="Mandiri" class="image-container">
							<span class="app-name">Mandiri</span>
						</a>
						<span class="app-item"></span><span class="app-item"></span><span class="app-item"></span><span class="app-item"></span><span class="app-item"></span><span class="app-item"></span></div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
setInterval(function()
{
	jQuery.ajax({
	  type:"get",
	  url:"<?php echo esc_url_raw(get_site_url()); ?>?wc-api=netzmeqr_gateway&action=checkstatus&key=<?php echo urlencode($this->encrypt($order_id)); ?>",
	  success:function(data)
	  {
		  if( data == '1'){
			<?php
			echo "t1 = window.setTimeout(function(){ window.location = '".esc_url_raw($order->get_checkout_order_received_url())."'; },3);";
			?>
		  }
	  }
	});
}, 3000);
</script>
