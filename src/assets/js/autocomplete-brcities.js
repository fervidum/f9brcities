class BrCitiesFormAutocomplete {

  /*<div class="dropdown">
    <input type='text' id='city' name='city' placeholder='Cidade' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' required>
    <ul class="dropdown-menu dropdown-autocomplete">
      <li>Joinville SC</li>
    </ul>
  </div>*/

  constructor(field) {
    this.field = field;

    this.wrapper();

    this.field.addEventListener('input', e => this.autocomplete(e));
  }

  autocomplete(e) {
    var value = e.target.value;
    if (value) {
      var cities = [];
      this.cities().forEach((city) => {
        if (city.substr(0, value.length).toUpperCase() === value.toUpperCase()) {
          cities.push(city);
        }
      });
      this.dropdownAutocomplete(cities);
    }
  }

  dropdownAutocomplete(cities) {
    if (cities.length > 0) {
      this.refreshField();
      this.dropdownList(cities);
    }
  }

  wrapper() {
    var wrapper = document.createElement('div');
    this.field.parentNode.insertBefore(wrapper, this.field);
    wrapper.appendChild(this.field);
    this.field.focus();
  }

  refreshField() {
    this.field.setAttribute('data-toggle', 'dropdown');
    this.field.setAttribute('aria-haspopup', 'true');
    this.field.setAttribute('aria-expanded', 'true');

    this.field.parentNode.classList.add('dropdown');
  }

  dropdownList(cities) {
    var menu;
    if (this.field.parentNode.querySelector('.dropdown-menu')) {
      menu = this.field.parentNode.querySelector('.dropdown-menu');
      menu.innerHTML = '';
    } else {
      menu = this.elementHTML('<ul class="dropdown-menu dropdown-autocomplete show">');
      this.field.parentNode.appendChild(menu);
    }
    cities.forEach((city) => {
      var item = this.elementHTML(`<li>${city}</li>`);
      item.addEventListener('click', e => this.chooseCity(e));
      menu.appendChild(item);
    })
  }

  chooseCity(e) {
    this.field.value = e.target.innerText;
    this.closeAutocomplete();
  }

  closeAutocomplete() {
    this.field.parentNode.querySelector('.dropdown-menu').classList.remove('show');
    this.field.parentNode.removeChild(this.field.parentNode.querySelector('.dropdown-menu'));
    this.field.parentNode.classList.remove('dropdown');
  }

  elementHTML(html) {
    var builder = document.createElement('div');
    builder.innerHTML = html.trim();
    return builder.firstChild;
  }

  cities() {
    return [
      'Jaborá SC',
      'Jacinto Machado SC',
      'Jaguaruna SC',
      'Jaraguá do Sul SC',
      'Jardinópolis SC',
      'Joaçaba SC',
      'Joinville SC',
      'José Boiteux SC',
      'Jupiá SC',
    ];
  }
}

new BrCitiesFormAutocomplete(document.querySelector('#city'));
