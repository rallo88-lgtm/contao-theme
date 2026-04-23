var klaroConfig = {
  version: 1,
  elementID: 'klaro',
  storageMethod: 'cookie',
  cookieName: 'rct_klaro',
  cookieExpiresAfterDays: 365,
  privacyPolicy: '/datenschutz',
  default: false,
  mustConsent: false,
  acceptAll: true,
  hideDeclineAll: false,
  lang: 'de',

  translations: {
    de: {
      privacyPolicyUrl: '/datenschutz',
      consentNotice: {
        description: 'Diese Website lädt externe Inhalte (Karten, Videos). Bitte stimme zu, um sie anzuzeigen. {purposes}',
        learnMore: 'Auswahl anpassen',
      },
      consentModal: {
        title: 'Datenschutzeinstellungen',
        description: 'Hier kannst du einstellen, welche externen Dienste geladen werden dürfen.',
      },
      acceptAll: 'Alle akzeptieren',
      declineAll: 'Alle ablehnen',
      acceptSelected: 'Auswahl speichern',
      purposes: {
        maps: 'Karten',
        media: 'Videos & Medien',
      },
      openstreetmap: {
        title: 'OpenStreetMap',
        description: 'Interaktive Karte via OpenStreetMap/Leaflet. Beim Laden werden Kartenkacheln vom OSM-Server abgerufen (IP-Übertragung).',
      },
      youtube: {
        title: 'YouTube',
        description: 'Video-Einbettung via YouTube (Google LLC). Beim Abspielen können Cookies gesetzt und Daten an Google übertragen werden.',
      },
    },
  },

  services: [
    {
      name: 'openstreetmap',
      title: 'OpenStreetMap',
      purposes: ['maps'],
      required: false,
      default: false,
    },
    {
      name: 'youtube',
      title: 'YouTube',
      purposes: ['media'],
      required: false,
      default: false,
    },
  ],
};
