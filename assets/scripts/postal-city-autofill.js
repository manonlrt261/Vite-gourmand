document.addEventListener('DOMContentLoaded', () => {
  const postalInputs = Array.from(document.querySelectorAll('input[name="code_postal"], input[name="code_postal_livraison"]'));
  const cityCache = new Map();

  const findCityInput = (postalInput) => {
    const form = postalInput.closest('form') || document;
    const cityName = postalInput.name === 'code_postal_livraison' ? 'ville_livraison' : 'ville';

    return form.querySelector(`input[name="${cityName}"]`);
  };

  const cleanPostalCode = (value) => value.replace(/\D/g, '').slice(0, 5);

  const fetchCities = async (postalCode) => {
    if (cityCache.has(postalCode)) {
      return cityCache.get(postalCode);
    }

    const response = await fetch(`https://geo.api.gouv.fr/communes?codePostal=${postalCode}&fields=nom&format=json`, {
      headers: {
        Accept: 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('postal_lookup_failed');
    }

    const cities = await response.json();
    const cityNames = Array.from(new Set(cities.map((city) => city.nom).filter(Boolean))).sort((first, second) => {
      return first.localeCompare(second, 'fr');
    });

    cityCache.set(postalCode, cityNames);

    return cityNames;
  };

  const getOrCreateCitySelect = (cityInput) => {
    let select = cityInput.parentElement.querySelector('[data-city-select]');

    if (select) {
      return select;
    }

    select = document.createElement('select');
    select.className = cityInput.className;
    select.dataset.citySelect = 'true';
    select.hidden = true;
    select.setAttribute('aria-label', 'Choisir la ville correspondant au code postal');
    cityInput.insertAdjacentElement('afterend', select);

    select.addEventListener('change', () => {
      cityInput.value = select.value;
      cityInput.dispatchEvent(new Event('input', { bubbles: true }));
      cityInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    return select;
  };

  const hideCitySelect = (cityInput) => {
    const select = cityInput.parentElement.querySelector('[data-city-select]');

    if (!select) {
      return;
    }

    select.hidden = true;
    select.required = false;
    select.innerHTML = '';
    cityInput.hidden = false;
    cityInput.required = cityInput.dataset.originalRequired === 'true';
  };

  const showCityChoices = (cityInput, cities) => {
    const select = getOrCreateCitySelect(cityInput);
    select.innerHTML = '<option value="">Choisissez votre ville</option>';

    cities.forEach((city) => {
      const option = document.createElement('option');
      option.value = city;
      option.textContent = city;
      select.appendChild(option);
    });

    cityInput.value = '';
    cityInput.hidden = true;
    cityInput.required = false;
    select.hidden = false;
    select.required = true;
  };

  const updateCityFromPostalCode = async (postalInput, cityInput) => {
    const postalCode = cleanPostalCode(postalInput.value);
    postalInput.value = postalCode;

    if (postalCode.length !== 5 || postalInput.disabled || cityInput.disabled) {
      hideCitySelect(cityInput);
      return;
    }

    try {
      const cities = await fetchCities(postalCode);

      if (cities.length === 1) {
        hideCitySelect(cityInput);
        cityInput.value = cities[0];
        cityInput.dispatchEvent(new Event('input', { bubbles: true }));
        cityInput.dispatchEvent(new Event('change', { bubbles: true }));
        return;
      }

      if (cities.length > 1) {
        showCityChoices(cityInput, cities);
      }
    } catch (error) {
      hideCitySelect(cityInput);
    }
  };

  postalInputs.forEach((postalInput) => {
    const cityInput = findCityInput(postalInput);

    if (!cityInput) {
      return;
    }

    cityInput.dataset.originalRequired = cityInput.required ? 'true' : 'false';

    postalInput.addEventListener('input', () => {
      const postalCode = cleanPostalCode(postalInput.value);
      postalInput.value = postalCode;

      if (postalCode.length < 5) {
        hideCitySelect(cityInput);
      }
    });

    postalInput.addEventListener('blur', () => {
      updateCityFromPostalCode(postalInput, cityInput);
    });

    postalInput.addEventListener('change', () => {
      updateCityFromPostalCode(postalInput, cityInput);
    });
  });
});
