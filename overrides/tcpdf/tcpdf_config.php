<?php
/* Enable overrides */
define('K_TCPDF_EXTERNAL_CONFIG', true);

/* Allow generate barcode image */
define('K_TCPDF_CALLS_IN_HTML', true);
define('K_ALLOWED_TCPDF_TAGS', '|write1DBarcode|');
