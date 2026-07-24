'use strict';

const menuButton = document.getElementById('menuButton');
const sidebar = document.getElementById('sidebar');

if (menuButton && sidebar) {
    menuButton.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
}

document.querySelectorAll('.alert-close').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('.alert')?.remove();
    });
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.dataset.confirm || 'Are you sure?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-table-search]').forEach((input) => {
    const tableId = input.dataset.tableSearch;
    const table = document.getElementById(tableId);

    if (!table) return;

    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        table.querySelectorAll('tbody tr').forEach((row) => {
            row.hidden = !row.textContent.toLowerCase().includes(query);
        });
    });
});

async function loadPlayersForMatch(matchSelect, playerSelect) {
    const matchId = matchSelect.value;
    const selectedPlayer = playerSelect.dataset.selectedPlayer || playerSelect.value;

    playerSelect.innerHTML = '<option value="">Select player</option>';
    playerSelect.disabled = true;

    if (!matchId) {
        return;
    }

    try {
        const response = await fetch(`api/players_by_match.php?match_id=${encodeURIComponent(matchId)}`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error('Could not load players.');
        }

        const players = await response.json();

        players.forEach((player) => {
            const option = document.createElement('option');
            option.value = player.player_id;
            option.textContent = `${player.player_name} — ${player.team_name} (${player.role})`;
            if (String(player.player_id) === String(selectedPlayer)) {
                option.selected = true;
            }
            playerSelect.appendChild(option);
        });

        playerSelect.disabled = false;
        playerSelect.dataset.selectedPlayer = '';
    } catch (error) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Unable to load players';
        playerSelect.appendChild(option);
        console.error(error);
    }
}

document.querySelectorAll('[data-player-match-select]').forEach((matchSelect) => {
    const targetId = matchSelect.dataset.playerMatchSelect;
    const playerSelect = document.getElementById(targetId);

    if (!playerSelect) return;

    matchSelect.addEventListener('change', () => loadPlayersForMatch(matchSelect, playerSelect));

    if (matchSelect.value) {
        loadPlayersForMatch(matchSelect, playerSelect);
    }
});

const team1Select = document.getElementById('team1_id');
const team2Select = document.getElementById('team2_id');
const winnerSelect = document.getElementById('winner_team_id');

function updateWinnerOptions() {
    if (!team1Select || !team2Select || !winnerSelect) return;

    const selectedWinner = winnerSelect.dataset.selectedWinner || winnerSelect.value;
    const options = [
        { value: '', label: 'Pending / No winner yet' }
    ];

    [team1Select, team2Select].forEach((select) => {
        const option = select.options[select.selectedIndex];
        if (option && option.value && !options.some((item) => item.value === option.value)) {
            options.push({ value: option.value, label: option.textContent });
        }
    });

    winnerSelect.innerHTML = '';
    options.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.value;
        option.textContent = item.label;
        if (String(item.value) === String(selectedWinner)) {
            option.selected = true;
        }
        winnerSelect.appendChild(option);
    });

    winnerSelect.dataset.selectedWinner = '';
}

if (team1Select && team2Select && winnerSelect) {
    team1Select.addEventListener('change', updateWinnerOptions);
    team2Select.addEventListener('change', updateWinnerOptions);
    updateWinnerOptions();
}
