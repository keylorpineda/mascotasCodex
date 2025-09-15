<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use Dompdf\Exception AS PDFException;

class PDF extends Dompdf
{
	public $ORIENTACION = "portrait"; // portrait: vertical | landscape: horizontal
	public $SIZE_PAPER  = "letter"; // letter: carta | folio: folio

	public function generar_pdf(string $view, ?string $ruta_archivo = null, bool $return = false)
	{
		$dompdf = new Dompdf(
			new Options(
				[
					'isPhpEnabled' => true,
					'isRemoteEnabled' => true,
					'defaultPaperSize' => $this->SIZE_PAPER,
					'defaultPaperMargin' => '0',
					"defaultPaperOrientation" => $this->ORIENTACION,
				]
			)
		);
		try {
			$dompdf->loadHtml($view);
			$dompdf->render();
			if ($return) { return $dompdf->output(); }
			return $dompdf->stream($ruta_archivo, ["Attachment" => $return]);
		} catch (PDFException $e) {
			throw new PDFException('Error al intentar generar el documento pdf: '.$e->getMessage());
		}
	}
}