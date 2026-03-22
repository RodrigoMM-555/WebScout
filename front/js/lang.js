// lang.js - Manejo de idioma para el front

const LANG_KEY = 'webscout_lang';
const DEFAULT_LANG = 'es';

// Traducciones (puedes expandir esto según lo que necesites traducir)
const translations = {
    es: {
        inicio: 'Inicio',
        avisos: 'Avisos',
        perfil: 'Perfil e hijos',
        salir: 'Salir',
        cambiar_idioma: 'English',
        hijos: 'Hijos',
        correo: 'Correo',
        telefono: 'Teléfono',
        direccion: 'Dirección',
        solicitud_ok: 'La solicitud ha sido procesada correctamente.',
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
    en: {
        inicio: 'Home',
        avisos: 'Notices',
        perfil: 'Profile & children',
        salir: 'Logout',
        cambiar_idioma: 'Español',
        hijos: 'Children',
        correo: 'Email',
        telefono: 'Phone',
        direccion: 'Address',
        solicitud_ok: 'The request has been processed successfully.',
        form_lista_espera: 'Waiting List Form',
        nombre_nino_label: "Child's name:",
        apellidos_nino_label: "Child's surname:",
        cambiar_contraseña: 'Change password',
        contraseña_actual: 'Current password',
        nueva_contraseña: 'New password',
        confirmar_contraseña: 'Confirm new password',
        seccion: 'Section',
        anio: 'Year',
        dni: 'ID',
        atras: 'Back',
        documentacion: 'Documentation',
        ficha_inscripcion: '1-Registration form',
        descargar: 'Download',
        elegir: 'Choose',
        sin_archivo: 'No file',
        subir: 'Upload',
        inicio_fecha: '📅 Start',
        fin_fecha: '🏁 End',
        lugar: '📍 Place',
        municipio: '🏘️ Town',
        provincia: '🗺️ Province',
        responsable: '👤 Responsible',
        secciones: '📋 Sections',
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
    const next = current === 'es' ? 'en' : 'es';
    setLang(next);
    window.location.reload();
}

function applyTranslations() {
    const lang = getCurrentLang();
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[lang][key]) {
            el.textContent = translations[lang][key];
        }
    });
    // Cambiar el texto del botón de idioma
    const btn = document.getElementById('lang-switch-btn');
    if (btn) {
        btn.textContent = translations[lang]['cambiar_idioma'];
    }
}

// Ejecutar traducción al cargar DOM y actualizar el botón si cambia el idioma
document.addEventListener('DOMContentLoaded', function() {
    applyTranslations();
    // Si el botón no existe aún, intentar de nuevo tras breve retardo
    setTimeout(applyTranslations, 100);
});