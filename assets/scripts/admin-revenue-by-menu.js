document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-revenue-menu-page]');

    if (!page) {
        return;
    }

    const documentsNode = document.getElementById('revenue-menu-documents');
    const statsNode = document.getElementById('revenue-menu-default-stats');
    const documents = JSON.parse(documentsNode ? documentsNode.textContent : '[]');
    const defaultStats = JSON.parse(statsNode ? statsNode.textContent : '{}');

    const startInput = page.querySelector('[data-revenue-menu-start]');
    const endInput = page.querySelector('[data-revenue-menu-end]');
    const themeSelect = page.querySelector('[data-revenue-menu-theme]');
    const menuSelect = page.querySelector('[data-revenue-menu-select]');
    const applyButton = page.querySelector('[data-revenue-menu-apply]');
    const resetButton = page.querySelector('[data-revenue-menu-reset]');
    const totalNode = page.querySelector('[data-revenue-menu-total]');
    const donutNode = page.querySelector('[data-revenue-menu-donut]');
    const donutTotalNode = page.querySelector('[data-revenue-menu-donut-total]');
    const legendNode = page.querySelector('[data-revenue-menu-legend]');
    const barsNode = page.querySelector('[data-revenue-menu-bars]');
    const tableNode = page.querySelector('[data-revenue-menu-table]');
    const emptyNode = page.querySelector('[data-revenue-menu-empty]');

    const currentYear = String(defaultStats.year || new Date().getFullYear());
    const themeColors = ['#b56320', '#e79c54', '#7e4214', '#f9c693', '#6d3b12'];
    const moneyFormatter = new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    });

    const formatMoney = (value) => moneyFormatter.format(Number(value || 0));
    const getMenuCheckboxes = () => Array.from(menuSelect.querySelectorAll('input[type="checkbox"]'));
    const getSelectedMenus = () => getMenuCheckboxes()
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const isInDateRange = (document) => {
        const date = document.date_commande || '';

        if (!startInput.value && !endInput.value) {
            return date.startsWith(currentYear);
        }

        if (startInput.value && date < startInput.value) {
            return false;
        }

        if (endInput.value && date > endInput.value) {
            return false;
        }

        return true;
    };

    const getFilteredDocuments = () => {
        const selectedTheme = themeSelect.value;
        const selectedMenus = getSelectedMenus();

        return documents.filter((document) => {
            const matchesDate = isInDateRange(document);
            const matchesTheme = !selectedTheme || document.theme === selectedTheme;
            const matchesMenu = selectedMenus.length === 0 || selectedMenus.includes(String(document.menu_id));

            return matchesDate && matchesTheme && matchesMenu;
        });
    };

    const groupByMenu = (items) => {
        const grouped = new Map();

        items.forEach((item) => {
            const key = String(item.menu_id);

            if (!grouped.has(key)) {
                grouped.set(key, {
                    menu_id: item.menu_id,
                    nom_menu: item.nom_menu,
                    theme: item.theme,
                    total: 0,
                });
            }

            grouped.get(key).total += Number(item.prix_total || 0);
        });

        return Array.from(grouped.values()).sort((a, b) => {
            if (b.total !== a.total) {
                return b.total - a.total;
            }

            return a.nom_menu.localeCompare(b.nom_menu);
        });
    };

    const groupByTheme = (items) => {
        const grouped = new Map();

        items.forEach((item) => {
            const key = item.theme || 'Non renseigné';
            grouped.set(key, (grouped.get(key) || 0) + Number(item.prix_total || 0));
        });

        return Array.from(grouped.entries()).map(([theme, total]) => ({ theme, total }));
    };

    const renderDonut = (items) => {
        const total = items.reduce((sum, item) => sum + item.total, 0);
        let cursor = 0;

        donutTotalNode.textContent = formatMoney(total);

        if (total === 0) {
            donutNode.style.background = '#fff7ed';
            legendNode.innerHTML = '<p class="employee-empty">Aucune donnée.</p>';
            return;
        }

        const gradientParts = items.map((item, index) => {
            const start = cursor;
            const end = cursor + (item.total / total) * 100;
            cursor = end;
            return `${themeColors[index % themeColors.length]} ${start}% ${end}%`;
        });

        donutNode.style.background = `conic-gradient(${gradientParts.join(', ')})`;
        legendNode.innerHTML = items.map((item, index) => {
            const percent = total > 0 ? ((item.total / total) * 100).toFixed(1).replace('.', ',') : '0';

            return `
                <div class="admin-orders-menu-legend-row admin-revenue-menu-legend-row">
                    <span style="background:${themeColors[index % themeColors.length]}"></span>
                    <p>${item.theme}</p>
                    <strong>${formatMoney(item.total)}</strong>
                    <small>${percent}%</small>
                </div>
            `;
        }).join('');
    };

    const renderBars = (items) => {
        const max = Math.max(...items.map((item) => item.total), 1);
        const topItems = items.slice(0, 8);

        barsNode.innerHTML = topItems.map((item) => {
            const height = Math.max((item.total / max) * 100, item.total > 0 ? 8 : 0);

            return `
                <article class="admin-orders-menu-bar">
                    <strong>${formatMoney(item.total)}</strong>
                    <span style="height:${height}%"></span>
                    <p title="${item.nom_menu}">${item.nom_menu}</p>
                </article>
            `;
        }).join('');
    };

    const renderTable = (items, total) => {
        if (items.length === 0) {
            tableNode.innerHTML = '';
            emptyNode.hidden = false;
            return;
        }

        emptyNode.hidden = true;
        tableNode.innerHTML = items.map((item) => {
            const percent = total > 0 ? ((item.total / total) * 100).toFixed(1).replace('.', ',') : '0';

            return `
                <tr>
                    <td>${item.nom_menu}</td>
                    <td><span class="admin-orders-menu-theme-badge">${item.theme}</span></td>
                    <td>${formatMoney(item.total)}</td>
                    <td>${percent}%</td>
                </tr>
            `;
        }).join('');
    };

    const render = () => {
        const filteredDocuments = getFilteredDocuments();
        const menuGroups = groupByMenu(filteredDocuments);
        const themeGroups = groupByTheme(filteredDocuments);
        const total = filteredDocuments.reduce((sum, document) => sum + Number(document.prix_total || 0), 0);

        totalNode.textContent = formatMoney(total);
        renderDonut(themeGroups);
        renderBars(menuGroups);
        renderTable(menuGroups, total);
    };

    page.addEventListener('keydown', (event) => {
        // La touche Entree applique les filtres comme le bouton principal.
        if (event.key === 'Enter' && event.target.matches('input, select')) {
            event.preventDefault();
            render();
        }
    });

    applyButton.addEventListener('click', render);

    resetButton.addEventListener('click', () => {
        startInput.value = '';
        endInput.value = '';
        themeSelect.value = '';
        getMenuCheckboxes().forEach((checkbox) => {
            checkbox.checked = false;
        });
        render();
    });

    render();
});
