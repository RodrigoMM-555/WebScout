// lang.js - Manejo de idioma para el front

const LANG_KEY = 'webscout_lang';
const DEFAULT_LANG = 'es';

// Traducciones (puedes expandir esto según lo que necesites traducir)
const translations = {
    es: {
            archivos_subidos: 'Archivos subidos',
            sin_archivos_subidos: 'Este educando aún no tiene archivos subidos.',
            nino: 'Niñ@',
            no_circular: 'No hay circular adjunta',
            asistencia: 'Asistencia',
            no_asiste: 'No asiste',
            si_asiste: 'Sí asiste',
            responder: 'RESPONDER',
            ronda: 'RONDA',
            trimestre: 'Trimestre',
            grupo: 'Grupo Scout Seeonee',
            localidad: 'Campanar, Valencia',
            lunes_corto: 'L',
            martes_corto: 'M',
            miercoles_corto: 'X',
            jueves_corto: 'J',
            viernes_corto: 'V',
            sabado_corto: 'S',
            domingo_corto: 'D',
            enero: 'Enero',
            febrero: 'Febrero',
            marzo: 'Marzo',
            abril: 'Abril',
            mayo: 'Mayo',
            junio: 'Junio',
            julio: 'Julio',
            agosto: 'Agosto',
            septiembre: 'Septiembre',
            octubre: 'Octubre',
            noviembre: 'Noviembre',
            diciembre: 'Diciembre',
        inicio: 'Inicio',
        avisos: 'Avisos',
        perfil: 'Perfil',
        salir: 'Salir',
        cambiar_idioma: '<div class="bandera-valencia"></div>',
        hijos: 'Hijos',
        correo: 'Correo',
        telefono: 'Teléfono',
        direccion: 'Dirección',
        solicitud_ok: 'La solicitud ha sido procesada correctamente.',
        obligatorio: '*Obligatorio',
        no_sesion: 'No se ha iniciado sesión.',
        sin_avisos: 'Sin avisos',
        ficha_inscripcion: '1-Ficha de inscripción',
        ficha_sanitaria: '2-Ficha sanitaria',
        ficha_exclusion: '3-Exclusión de responsabilidad',
        ficha_autorizacion: '4-Autorización ausentarse de actividades',
        form_lista_espera: 'Formulario de lista de espera',
        nombre_nino_label: 'Nombre del niño:',
        apellidos_nino_label: 'Apellidos del niño:',
        cambiar_contraseña: 'Cambiar contraseña',
        contraseña_actual: 'Contraseña actual',
        nueva_contraseña: 'Nueva contraseña',
        confirmar_contraseña: 'Confirmar nueva contraseña',
        seccion: 'Sección',
        anio: 'Año',
        dni: 'DNI',
        atras: 'Atrás',
        documentacion: 'Documentación',
        ficha_inscripcion: '1-Ficha de inscripción',
        descargar: 'Descargar',
        elegir: 'Elegir',
        sin_archivo: 'Sin archivo',
        subir: 'Subir',
        inicio_fecha: '📅 Inicio',
        fin_fecha: '🏁 Fin',
        lugar: '📍 Lugar',
        municipio: '🏘️ Municipio',
        provincia: '🗺️ Provincia',
        responsable: '👤 Responsable',
        secciones: '📋 Secciones',
    },
    va: {
            archivos_subidos: 'Arxius pujats',
            sin_archivos_subidos: 'Aquest educand encara no té arxius pujats.',
            nino: 'Xiquet/a',
            no_circular: 'No hi ha circular adjunta',
            asistencia: 'Assistència',
            no_asiste: 'No assisteix',
            si_asiste: 'Sí assisteix',
            responder: 'RESPONDRE',
            ronda: 'RONDA',
            trimestre: 'Trimestre',
            grupo: 'Grup Scout Seeonee',
            localidad: 'Campanar, València',
            lunes_corto: 'Dl',
            martes_corto: 'Dt',
            miercoles_corto: 'Dc',
            jueves_corto: 'Dj',
            viernes_corto: 'Dv',
            sabado_corto: 'Ds',
            domingo_corto: 'Dg',
            enero: 'Gener',
            febrero: 'Febrer',
            marzo: 'Març',
            abril: 'Abril',
            mayo: 'Maig',
            junio: 'Juny',
            julio: 'Juliol',
            agosto: 'Agost',
            septiembre: 'Setembre',
            octubre: 'Octubre',
            noviembre: 'Novembre',
            diciembre: 'Desembre',
        inicio: 'Inici',
        avisos: 'Avisos',
        perfil: 'Perfil',
        salir: 'Eixir',
        cambiar_idioma: '<div class="bandera-espana"></div>',
        hijos: 'Fills',
        correo: 'Correu',
        telefono: 'Telèfon',
        direccion: 'Adreça',
        solicitud_ok: 'La sol·licitud s\'ha processat correctament.',
        obligatorio: '*Obligatori',
        no_sesion: 'No s\'ha iniciat sessió.',
        sin_avisos: 'Sense avisos',
        ficha_inscripcion: '1-Fitxa d\'inscripció',
        ficha_sanitaria: '2-Fitxa sanitària',
        ficha_exclusion: '3-Exclusió de responsabilitat',
        ficha_autorizacion: '4-Autorització absentar-se d\'activitats',
        form_lista_espera: 'Formulari de llista d\'espera',
        nombre_nino_label: 'Nom del xiquet:',
        apellidos_nino_label: 'Cognoms del xiquet:',
        cambiar_contraseña: 'Canviar contrasenya',
        contraseña_actual: 'Contrasenya actual',
        nueva_contraseña: 'Nova contrasenya',
        confirmar_contraseña: 'Confirmar nova contrasenya',
        seccion: 'Secció',
        anio: 'Any',
        dni: 'DNI',
        atras: 'Arrere',
        documentacion: 'Documentació',
        ficha_inscripcion: '1-Fitxa d\'inscripció',
        descargar: 'Descarregar',
        elegir: 'Triar',
        sin_archivo: 'Sense fitxer',
        subir: 'Pujar',
        inicio_fecha: '📅 Inici',
        fin_fecha: '🏁 Fi',
        lugar: '📍 Lloc',
        municipio: '🏘️ Municipi',
        provincia: '🗺️ Província',
        responsable: '👤 Responsable',
        secciones: '📋 Seccions',
    }
};

function getCurrentLang() {
    return localStorage.getItem(LANG_KEY) || DEFAULT_LANG;
}

function setLang(lang) {
    localStorage.setItem(LANG_KEY, lang);
}

function toggleLang() {
    const current = getCurrentLang();
    const next = current === 'es' ? 'va' : 'es';
    setLang(next);
    window.location.reload();
}

function applyTranslations() {
    const lang = getCurrentLang();
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        // Si el elemento es un input hidden de ficha, no cambiar textContent
        if (el.tagName === 'INPUT' && el.type === 'hidden' && key && key.startsWith('ficha_')) return;
        if (translations[lang][key]) {
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                el.value = translations[lang][key];
            } else {
                el.textContent = translations[lang][key];
            }
        }
    });
    // Actualizar los value de los input hidden de fichas
    if (typeof updateFichaHiddenValues === 'function') updateFichaHiddenValues();
    // Cambiar el texto del botón de idioma
    const btn = document.getElementById('lang-switch-btn');
    if (btn) {
        btn.innerHTML = translations[lang]['cambiar_idioma'];
    }
}

// Ejecutar traducción al cargar DOM y actualizar el botón si cambia el idioma
document.addEventListener('DOMContentLoaded', function() {
    applyTranslations();
    // Si el botón no existe aún, intentar de nuevo tras breve retardo
    setTimeout(applyTranslations, 100);
});