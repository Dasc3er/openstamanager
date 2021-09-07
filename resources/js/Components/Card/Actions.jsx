import Component from '../Component';

export default class Actions extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-card__actions', {
      'mdc-card__actions--full-bleed': this.attrs.has('full-bleed')
    });
    return (
      <div {...this.attrs.all()}>
        {vnode.children}
      </div>
    );
  }
}