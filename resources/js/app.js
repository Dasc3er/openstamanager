import '../scss/app.scss';
import './_material';

import {createInertiaApp} from '@maicol07/inertia-mithril';
import {waitUntil} from 'async-wait-until';
import jQuery from 'jquery';
import m from 'mithril';

// Fix Mithril JSX durante la compilazione
m.Fragment = '[';

// Variabili globali
window.$ = jQuery;
window.jQuery = jQuery;
window.m = m;

// noinspection JSIgnoredPromiseFromCall
createInertiaApp({
  title: title => `${title} - OpenSTAManager`,
  resolve: async (name) => {
    const split = name.split('::');

    if (split.length === 1) {
      return (await import(`./Views/${name}.jsx`)).default;
    }

    const [, page] = split;
    // noinspection JSUnresolvedVariable
    await waitUntil(() => typeof window.extmodule !== 'undefined');
    // noinspection JSUnresolvedVariable
    return window.extmodule[page];
  },
  setup({ el, app }) {
    m.mount(el, app);
  }
});