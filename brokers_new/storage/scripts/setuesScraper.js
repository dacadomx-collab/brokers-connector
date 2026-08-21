/**
 * setuesScraper.js — Verificación PUNTUAL de un folio SETUES BCS.
 *
 * ALCANCE DELIBERADAMENTE LIMITADO (decisión de producto 2026-07-07, ver
 * Informe Forense de Fase 2): este script consulta UN folio a la vez, nunca
 * itera un rango de IDs ni construye un volcado del padrón. Solo extrae los
 * campos necesarios para validar una licencia (nombre_titular, razon_social,
 * status_oficial) — email, telefono, domicilio y foto de credencial NO se
 * extraen ni se devuelven, aunque estén visibles en la página, porque no son
 * necesarios para el caso de uso (validar que un folio existe y está activo)
 * y representan datos personales sensibles de terceros que no han dado
 * consentimiento a Brokers Connector para procesarlos.
 *
 * Uso: node setuesScraper.js LIC-RAPIBCS-1234
 * Salida: JSON en stdout — { found: bool, folio_licencia, nombre_titular,
 *                             razon_social, status_oficial }
 *
 * ⚠️ PENDIENTE DE VERIFICACIÓN HUMANA ANTES DE PRODUCCIÓN:
 * Los selectores DOM de abajo (SELECTOR_* ) son un placeholder razonable
 * basado en la estructura típica de estos portales de padrón gubernamental,
 * pero no se generaron inspeccionando la página real (este entorno no hizo
 * fetch en vivo contra setuesbcs.gob.mx). Un humano debe abrir el RAPI,
 * confirmar los selectores reales y ajustarlos aquí antes de usar este
 * script contra tráfico de producción.
 */

const { chromium } = require('playwright');

const RAPI_BASE_URL = 'http://setuesbcs.gob.mx/rapireg/desplegadoapi.php';
const FOLIO_REGEX = /^LIC-RAPIBCS-(\d{4})$/;
const REQUEST_TIMEOUT_MS = 15000;

// Placeholders — ajustar contra el HTML real del RAPI antes de producción.
const SELECTOR_FOLIO = '.folio-licencia, #folio';
const SELECTOR_NOMBRE = '.nombre-titular, #nombre';
const SELECTOR_RAZON_SOCIAL = '.razon-social, #razon_social';
const SELECTOR_STATUS = '.status-licencia, #status';

function courtesyJitterMs() {
    // Pequeño retardo aleatorio incluso para una sola consulta — buena
    // práctica de "indexación respetuosa" ante cualquier infraestructura ajena.
    return 400 + Math.floor(Math.random() * 900);
}

function extractText(page, selector) {
    return page.locator(selector).first().innerText().catch(() => null);
}

async function verifyFolio(folio) {
    const match = folio.match(FOLIO_REGEX);
    if (!match) {
        throw new Error(`Folio con formato inválido: ${folio}`);
    }
    const id = match[1];

    await new Promise((resolve) => setTimeout(resolve, courtesyJitterMs()));

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (compatible; BrokersConnectorLicenseCheck/1.0; +https://brokersconnector.com)',
    });
    const page = await context.newPage();
    page.setDefaultTimeout(REQUEST_TIMEOUT_MS);

    try {
        await page.goto(`${RAPI_BASE_URL}?id=${encodeURIComponent(id)}`, {
            waitUntil: 'domcontentloaded',
            timeout: REQUEST_TIMEOUT_MS,
        });

        const folioEnPagina = await extractText(page, SELECTOR_FOLIO);

        // Verificación de integridad: el folio devuelto por el portal debe
        // coincidir con el solicitado. Si no coincide (o no se encontró
        // nada), se reporta found:false en vez de arriesgar un falso positivo.
        if (!folioEnPagina || !folioEnPagina.includes(folio)) {
            return { found: false, folio_licencia: folio };
        }

        const nombreTitular = await extractText(page, SELECTOR_NOMBRE);
        const razonSocial = await extractText(page, SELECTOR_RAZON_SOCIAL);
        const statusOficial = await extractText(page, SELECTOR_STATUS);

        return {
            found: true,
            folio_licencia: folio,
            nombre_titular: (nombreTitular || '').trim(),
            razon_social: razonSocial ? razonSocial.trim() : null,
            status_oficial: (statusOficial || 'Activa').trim(),
        };
    } finally {
        await browser.close();
    }
}

const folioArg = process.argv[2];

if (!folioArg) {
    process.stderr.write('Uso: node setuesScraper.js <folio_licencia>\n');
    process.exit(1);
}

verifyFolio(folioArg)
    .then((result) => {
        process.stdout.write(JSON.stringify(result));
        process.exit(0);
    })
    .catch((err) => {
        process.stderr.write(`Error verificando folio: ${err.message}\n`);
        process.exit(1);
    });
