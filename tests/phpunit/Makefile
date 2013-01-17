SHELL = /bin/sh
PHP = php
# This might work. If it fails, try changing it.
MWPATH = ${MW_INSTALL_PATH}
PU = ${PHP} ${MWPATH}/tests/phpunit/phpunit.php
PUCOV = ${PU} --coverage-html coverage .

tests:
	${PU} .

config:
	${PU} EtherEditorTest.php

includes:
	${PU} includes/

api:
	${PU} api/

coverage:
	${PUCOV}
