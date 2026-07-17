// Construit côté client les statistiques de commandes par menu à partir des données JSON intégrées à la page.
document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-orders-menu-page]');

    if (!page) {
        return;
    }

    const documentsNode = document.getElementById('orders-menu-documents');
    const statsNode = document.getElementById('orders-menu-default-stats');
    const documents = JSON.parse(documentsNode ? documentsNode.textContent : '[]');
    const defaultStats = JSON.parse(statsNode ? statsNode.textContent : '{}');

    const startInput = page.querySelector('[data-orders-menu-start]');
    const endInput = page.querySelector('[data-orders-menu-end]');
    const themeSelect = page.querySelector('[data-orders-menu-theme]');
    const menuSelect = page.querySelector('[data-orders-menu-select]');
    const applyButton = page.querySelector('[data-orders-menu-apply]');
    const resetButton = page.querySelector('[data-orders-menu-reset]');
    const totalNode = page.querySelector('[data-orders-menu-total]');
    const donutNode = page.querySelector('[data-orders-menu-donut]');
    const donutTotalNode = page.querySelector('[data-orders-menu-donut-total]');
    const legendNode = page.querySelector('[data-orders-menu-legend]');
    const barsNode = page.querySelector('[data-orders-menu-bars]');
    const tableNode = page.querySelector('[data-orders-menu-table]');
    const emptyNode = page.querySelector('[data-orders-menu-empty]');

    const currentYear = String(defaultStats.year || new Date().getFullYear());
    const themeColors = ['#b56320', '#e79c54', '#7e4214', '#f9c693', '#6d3b12'];

    const getMenuCheckboxes = () => Array.from(menuSelect.querySelectorAll('input[type="checkbox"]'));
    const getSelectedMenus = () => getMenuCheckboxes()
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const isInDateRange = (document) => {
        const date = document.date_commande || '';

        // Sans période explicite, le tableau de bord reste limité à l'année de référence.
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
        // Les filtres actifs sont cumulatifs ; une sélection de menus vide signifie « tous les menus ».
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
        // Agrège une ligne par menu, puis classe les menus par nombre de commandes décroissant.
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

            grouped.get(key).total += 1;
        });

        return Array.from(grouped.values()).sort((a, b) => {
            if (b.total !== a.total) {
                return b.total - a.total;
            }

            return a.nom_menu.localeCompare(b.nom_menu);
        });
    };

    const groupByTheme = (items) => {
        // Produit les totaux attendus par le graphique circulaire, indépendamment du détail par menu.
        const grouped = new Map();

        items.forEach((item) => {
            const key = item.theme || 'Non renseigné';
            grouped.set(key, (grouped.get(key) || 0) + 1);
        });

        return Array.from(grouped.entries()).map(([theme, total]) => ({ theme, total }));
    };

    const renderDonut = (items) => {
        // Chaque groupe occupe dans le dégradé conique une portion proportionnelle à son total.
        const total = items.reduce((sum, item) => sum + item.total, 0);
        let cursor = 0;

        donutTotalNode.textContent = total;

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
                <div class="admin-orders-menu-legend-row">
                    <span style="background:${themeColors[index % themeColors.length]}"></span>
                    <p>${item.theme}</p>
                    <strong>${item.total}</strong>
                    <small>${percent}%</small>
                </div>
            `;
        }).join('');
    };

    const renderBars = (items) => {
        // Limite le graphique aux huit menus les plus commandés et conserve une hauteur minimale visible.
        const max = Math.max(...items.map((item) => item.total), 1);
        const topItems = items.slice(0, 8);

        barsNode.innerHTML = topItems.map((item) => {
            const height = Math.max((item.total / max) * 100, item.total > 0 ? 8 : 0);

            return `
                <article class="admin-orders-menu-bar">
                    <strong>${item.total}</strong>
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
                    <td>${item.total}</td>
                    <td>${percent}%</td>
                </tr>
            `;
        }).join('');
    };

    const render = () => {
        // Recalcule toutes les représentations à partir d'un même jeu de données filtré.
        const filteredDocuments = getFilteredDocuments();
        const menuGroups = groupByMenu(filteredDocuments);
        const themeGroups = groupByTheme(filteredDocuments);
        const total = filteredDocuments.length;

        totalNode.textContent = total;
        renderDonut(themeGroups);
        renderBars(menuGroups);
        renderTable(menuGroups, total);
    };

    page.addEventListener('keydown', (event) => {
        // La touche Entrée applique les filtres comme le bouton principal.
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
