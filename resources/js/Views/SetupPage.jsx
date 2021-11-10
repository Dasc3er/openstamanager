import '@material/mwc-button';
import '@material/mwc-checkbox';
import '@material/mwc-fab';
import '@material/mwc-formfield';
import '@material/mwc-list/mwc-list-item';
import '@material/mwc-select';
import '@material/mwc-textarea';
import '../WebComponents/TextField';

import collect from 'collect.js';
import LocaleCode from 'locale-code';
import Mithril from 'mithril';

// eslint-disable-next-line import/no-absolute-path
import logoUrl from '/images/logo_completo.png';

import Card from '../Components/Card/Card.jsx';
import Content from '../Components/Card/Content.jsx';
import Cell from '../Components/Grid/Cell.jsx';
import LayoutGrid from '../Components/Grid/LayoutGrid.jsx';
import Row from '../Components/Grid/Row.jsx';
import Mdi from '../Components/Mdi.jsx';
import Page from '../Components/Page.jsx';

export default class SetupPage extends Page {
  languages() {
    const listItems: Array[Mithril.Vnode] = [];

    for (const lang of this.page.props.languages) {
      const attributes = {
        selected: this.page.props.locale === lang
      };
      const langCode = lang.replace('_', '-');
      listItems.push(
        <mwc-list-item graphic="icon" value={lang} {...attributes}>
          <img
            slot="graphic"
            style="border-radius: 4px;"
            src={`https://lipis.github.io/flag-icon-css/flags/4x3/${LocaleCode.getCountryCode(langCode)
              .toLowerCase()}.svg`}
            alt={LocaleCode.getLanguageNativeName(langCode)}>
          </img>
          <span>{LocaleCode.getLanguageNativeName(langCode)}</span>
        </mwc-list-item>
      );
    }

    return listItems;
  }

  view(vnode) {
    const examplesTexts = collect();
    for (const example of ['localhost', 'root', 'mysql', 'openstamanager']) {
      examplesTexts.put(example, __('Esempio: :example', {example}));
    }

    return (
      <>
        <Card outlined className="center" style="width: 85%;">
          <Content>
            <img src={logoUrl} className="center" alt={__('OpenSTAManager')} />
            <LayoutGrid>
              <Row>
                <Cell columnspan-desktop="8">
                  <h2>{__('Benvenuto in :name!', {name: <strong>{__('OpenSTAManager')}</strong>})}</h2>
                  <p>{__('Puoi procedere alla configurazione tecnica del software attraverso i '
                    + 'parametri seguenti, che potranno essere corretti secondo necessità tramite il file .env.')}<br/>
                    {__("Se necessiti supporto puoi contattarci tramite l':contactLink o tramite il nostro :forumLink.", {
                      // eslint-disable-next-line no-secrets/no-secrets
                      contactLink: <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM">{__('assistenza ufficiale')}</a>,
                      forumLink: <a href="https://forum.openstamanager.com">{__('forum')}</a>
                    })}</p>
                  <h4>{__('Formato date')}</h4>
                  <small>
                    {__('I formati sono impostabili attraverso lo standard previsto da :link.',
                      {link: <a href="https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters">PHP</a>})
                    }
                  </small>
                  <Row style="margin-top: 8px;">
                    <Cell>
                      <text-field name="timestamp_format" label={__('Formato data lunga')}
                                  required value="d/m/Y H:i">
                        <Mdi icon="calendar-clock" slot="icon"/>
                      </text-field>
                    </Cell>
                    <Cell>
                      <text-field name="date_format" label={__('Formato data corta')}
                                  required value="d/m/Y">
                        <Mdi icon="calendar-month-outline" slot="icon"/>
                      </text-field>
                    </Cell>
                    <Cell>
                      <text-field name="time_format" label={__('Formato orario')} required
                                  value="H:i">
                        <Mdi icon="clock-outline" slot="icon"/>
                      </text-field>
                    </Cell>
                  </Row>
                  <hr/>
                  <h4>{__('Database')}</h4>
                  <Row>
                    <Cell columnspan="4">
                      <text-field name="host" label={__('Host')} required
                                  helper={examplesTexts.get('localhost')}>
                        <Mdi icon="server-network" slot="icon"/>
                      </text-field>
                    </Cell>
                    <Cell columnspan="4">
                      <text-field name="username" label={__('Nome utente')} required
                                  helper={examplesTexts.get('root')}>
                        <Mdi icon="account-outline" slot="icon"/>
                      </text-field>
                    </Cell>
                    <Cell columnspan="4">
                      <text-field name="password" label={__('Password')}
                                  helper={examplesTexts.get('mysql')}>
                        <Mdi icon="lock-outline" slot="icon"/>
                      </text-field>
                    </Cell>
                    <Cell columnspan="4">
                      <text-field name="database_name" label={__('Nome database')} required
                                  helper={examplesTexts.get('openstamanager')}>
                        <Mdi icon="database-outline" slot="icon"/>
                      </text-field>
                    </Cell>
                  </Row>
                  <hr/>
                  <Row>
                    <Cell>
                      <small>{__('* Campi obbligatori')}</small>
                    </Cell>
                    <Cell>
                      <mwc-button raised label={__('Salva e installa')}>
                        <Mdi icon="check" slot="icon"/>
                      </mwc-button>
                    </Cell>
                    <Cell>
                      <mwc-button outlined label={__('Testa il database')}>
                        <Mdi icon="test-tube" slot="icon"/>
                      </mwc-button>
                    </Cell>
                  </Row>
                </Cell>
                <Cell>
                  <h4>{__('Lingua')}</h4>
                  <mwc-select>
                    {this.languages()}
                  </mwc-select>
                  <hr />
                  <h4>{__('Licenza')}</h4>
                  <p>{__('OpenSTAManager è tutelato dalla licenza GPL 3.0, da accettare obbligatoriamente per poter utilizzare il gestionale.')}</p>
                  <mwc-textarea value={this.page.props.license} rows="15" cols="40" disabled />
                  <Row style="margin-top: 5px;">
                    <Cell columnspan-desktop="8" columnspan-tablet="8">
                      <mwc-formfield label={__('Ho visionato e accetto la licenza')}>
                        <mwc-checkbox name="license_agreement"/>
                      </mwc-formfield>
                    </Cell>
                    <Cell>
                      <a href="https://www.gnu.org/licenses/translations.en.html#GPL" target="_blank">
                        <mwc-button label={__('Versioni tradotte')}>
                          <Mdi icon="license" slot="icon"/>
                        </mwc-button>
                      </a>
                    </Cell>
                  </Row>
                </Cell>
              </Row>
            </LayoutGrid>
          </Content>
        </Card>
        <mwc-fab id="contrast-switcher" className="sticky contrast-light"
                 label={__('Attiva/disattiva contrasto elevato')}>
          <Mdi icon="contrast-circle" slot="icon" className="light-bg"/>
        </mwc-fab>
      </>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM) {
    super.oncreate(vnode);

    $('mwc-fab#contrast-switcher')
      .on('click', function () {
        $(this)
          .toggleClass('contrast-light')
          .toggleClass('contrast-dark');
        $('body')
          .toggleClass('mdc-high-contrast');
      });

    // Fix for mwc button inside <a> tags
    $('a')
      .has('mwc-button')
      .css('text-decoration', 'none');
  }
}
