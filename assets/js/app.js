'use strict';

/* =========================================================
   MOBILE SIDEBAR
   ========================================================= */

const menuButton = document.getElementById('menuButton');
const sidebar = document.getElementById('sidebar');

if (menuButton && sidebar) {
    menuButton.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
}


/* =========================================================
   ALERT CLOSE BUTTON
   ========================================================= */

document.querySelectorAll('.alert-close').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('.alert')?.remove();
    });
});


/* =========================================================
   DELETE CONFIRMATION
   ========================================================= */

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.dataset.confirm || 'Are you sure?';

        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});


/* =========================================================
   TABLE SEARCH
   ========================================================= */

document.querySelectorAll('[data-table-search]').forEach((input) => {
    const tableId = input.dataset.tableSearch;
    const table = document.getElementById(tableId);

    if (!table) {
        return;
    }

    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();

        table.querySelectorAll('tbody tr').forEach((row) => {
            row.hidden = !row.textContent.toLowerCase().includes(query);
        });
    });
});


/* =========================================================
   LOAD PLAYERS BY MATCH
   ========================================================= */

async function loadPlayersForMatch(matchSelect, playerSelect) {
    const matchId = matchSelect.value;
    const selectedPlayer =
        playerSelect.dataset.selectedPlayer || playerSelect.value;

    playerSelect.innerHTML =
        '<option value="">Select player</option>';

    playerSelect.disabled = true;

    if (!matchId) {
        return;
    }

    try {
        const response = await fetch(
            `api/players_by_match.php?match_id=${encodeURIComponent(matchId)}`,
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            }
        );

        if (!response.ok) {
            throw new Error('Could not load players.');
        }

        const players = await response.json();

        players.forEach((player) => {
            const option = document.createElement('option');

            option.value = player.player_id;

            option.textContent =
                `${player.player_name} — ` +
                `${player.team_name} (${player.role})`;

            if (
                String(player.player_id) ===
                String(selectedPlayer)
            ) {
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

document
    .querySelectorAll('[data-player-match-select]')
    .forEach((matchSelect) => {
        const targetId =
            matchSelect.dataset.playerMatchSelect;

        const playerSelect =
            document.getElementById(targetId);

        if (!playerSelect) {
            return;
        }

        matchSelect.addEventListener('change', () => {
            loadPlayersForMatch(
                matchSelect,
                playerSelect
            );
        });

        if (matchSelect.value) {
            loadPlayersForMatch(
                matchSelect,
                playerSelect
            );
        }
    });


/* =========================================================
   OLD WINNER SELECT SUPPORT
   ========================================================= */

const team1Select =
    document.getElementById('team1_id');

const team2Select =
    document.getElementById('team2_id');

const winnerSelect =
    document.getElementById('winner_team_id');

function updateWinnerOptions() {
    if (
        !team1Select ||
        !team2Select ||
        !winnerSelect
    ) {
        return;
    }

    const selectedWinner =
        winnerSelect.dataset.selectedWinner ||
        winnerSelect.value;

    const options = [
        {
            value: '',
            label: 'Pending / No winner yet'
        }
    ];

    [team1Select, team2Select].forEach((select) => {
        const selectedOption =
            select.options[select.selectedIndex];

        if (
            selectedOption &&
            selectedOption.value &&
            !options.some(
                (item) =>
                    item.value === selectedOption.value
            )
        ) {
            options.push({
                value: selectedOption.value,
                label: selectedOption.textContent
            });
        }
    });

    winnerSelect.innerHTML = '';

    options.forEach((item) => {
        const option =
            document.createElement('option');

        option.value = item.value;
        option.textContent = item.label;

        if (
            String(item.value) ===
            String(selectedWinner)
        ) {
            option.selected = true;
        }

        winnerSelect.appendChild(option);
    });

    winnerSelect.dataset.selectedWinner = '';
}

if (
    team1Select &&
    team2Select &&
    winnerSelect
) {
    team1Select.addEventListener(
        'change',
        updateWinnerOptions
    );

    team2Select.addEventListener(
        'change',
        updateWinnerOptions
    );

    updateWinnerOptions();
}


/* =========================================================
   LIVE MATCH FORM REFERENCES
   ========================================================= */

const matchForm =
    document.getElementById('matchForm');

const matchStateSelect =
    document.getElementById('match_state');

const inningsFields =
    document.getElementById('inningsFields');

const liveSaveStatus =
    document.getElementById('liveSaveStatus');

const matchSubmitButton =
    document.getElementById('matchSubmitButton') ||
    matchForm?.querySelector(
        '.form-actions button[type="submit"]'
    ) ||
    null;

const matchIdInput =
    matchForm?.querySelector(
        'input[name="match_id"]'
    ) ?? null;

const liveScoreFieldIds = [
    'team1_score',
    'team1_wickets',
    'team1_overs',
    'team2_score',
    'team2_wickets',
    'team2_overs'
];

const liveScoreFields =
    liveScoreFieldIds
        .map((id) =>
            document.getElementById(id)
        )
        .filter(
            (field) =>
                field instanceof HTMLInputElement
        );


/* =========================================================
   LIVE AUTO-SAVE VARIABLES
   ========================================================= */

let liveSaveTimer = null;
let liveSaveInProgress = false;
let liveSaveQueued = false;
let lastSavedSnapshot = '';


/* =========================================================
   BASIC LIVE MATCH HELPERS
   ========================================================= */

function isLiveMatchForm() {
    return Boolean(
        matchStateSelect &&
        matchStateSelect.value === 'live'
    );
}

function currentMatchId() {
    return (
        Number.parseInt(
            matchIdInput?.value || '0',
            10
        ) || 0
    );
}


/* =========================================================
   AUTO-SAVE STATUS MESSAGE
   ========================================================= */

function setLiveSaveStatus(
    message,
    state = 'ready'
) {
    if (!liveSaveStatus) {
        return;
    }

    liveSaveStatus.hidden = false;
    liveSaveStatus.textContent = message;
    liveSaveStatus.dataset.state = state;

    const stateColors = {
        ready: '#166534',
        saving: '#64748b',
        saved: '#15803d',
        error: '#b91c1c'
    };

    liveSaveStatus.style.display = 'block';
    liveSaveStatus.style.minHeight = '20px';
    liveSaveStatus.style.fontWeight = '700';

    liveSaveStatus.style.color =
        stateColors[state] ||
        stateColors.ready;
}

function clearLiveSaveStatus() {
    if (!liveSaveStatus) {
        return;
    }

    liveSaveStatus.hidden = true;
    liveSaveStatus.textContent = '';

    liveSaveStatus.removeAttribute(
        'data-state'
    );
}


/* =========================================================
   MATCH STATUS FIELD CONTROL
   ========================================================= */

function updateMatchScoreFields() {
    if (
        !matchStateSelect ||
        !inningsFields
    ) {
        return;
    }

    const isPending =
        matchStateSelect.value === 'pending';

    const isLive =
        matchStateSelect.value === 'live';

    const isCompleted =
        matchStateSelect.value === 'completed';

    inningsFields.classList.toggle(
        'is-pending',
        isPending
    );

    inningsFields
        .querySelectorAll('input')
        .forEach((input) => {
            input.disabled = isPending;
        });

    [
        'team1_score',
        'team2_score'
    ].forEach((id) => {
        const input =
            document.getElementById(id);

        if (input) {
            input.required = isCompleted;
        }
    });

    /*
     * Existing Live match হলে
     * Update Match button hide হবে।
     */
    if (matchSubmitButton) {
        const isExistingLiveMatch =
            isLive &&
            currentMatchId() > 0;

        matchSubmitButton.hidden =
            isExistingLiveMatch;
    }

    if (isLive) {
        if (currentMatchId() > 0) {
            setLiveSaveStatus(
                'Live auto-save is active. Change any score value to save automatically.',
                'ready'
            );
        } else {
            setLiveSaveStatus(
                'Select both teams and enter a score. The live match will be created automatically.',
                'ready'
            );
        }
    } else {
        window.clearTimeout(
            liveSaveTimer
        );

        liveSaveTimer = null;

        clearLiveSaveStatus();
    }
}


/* =========================================================
   CRICKET OVERS VALIDATION
   ========================================================= */

function isValidCricketOvers(value) {
    const trimmedValue =
        String(value).trim();

    if (trimmedValue === '') {
        return true;
    }

    /*
     * Valid examples:
     * 0
     * 0.2
     * 15
     * 19.5
     *
     * Invalid:
     * 10.6
     * 5.8
     */
    return /^\d+(?:\.[0-5])?$/.test(
        trimmedValue
    );
}


/* =========================================================
   VALIDATE LIVE FORM BEFORE AJAX SAVE
   ========================================================= */

function validateLiveFormBeforeSave() {
    if (
        !matchForm ||
        !matchStateSelect
    ) {
        return 'Live match form is unavailable.';
    }

    const team1Id =
        document.getElementById(
            'team1_id'
        )?.value || '';

    const team2Id =
        document.getElementById(
            'team2_id'
        )?.value || '';

    const matchDate =
        document.getElementById(
            'match_date'
        )?.value || '';

    const team1Wickets =
        Number.parseInt(
            document.getElementById(
                'team1_wickets'
            )?.value || '0',
            10
        );

    const team2Wickets =
        Number.parseInt(
            document.getElementById(
                'team2_wickets'
            )?.value || '0',
            10
        );

    const team1Overs =
        document.getElementById(
            'team1_overs'
        )?.value || '';

    const team2Overs =
        document.getElementById(
            'team2_overs'
        )?.value || '';

    if (!team1Id || !team2Id) {
        return 'Select both teams before entering the live score.';
    }

    if (team1Id === team2Id) {
        return 'A team cannot play against itself.';
    }

    if (!matchDate) {
        return 'Select the match date first.';
    }

    if (
        Number.isNaN(team1Wickets) ||
        Number.isNaN(team2Wickets) ||
        team1Wickets < 0 ||
        team1Wickets > 10 ||
        team2Wickets < 0 ||
        team2Wickets > 10
    ) {
        return 'Wickets must be between 0 and 10.';
    }

    if (
        !isValidCricketOvers(
            team1Overs
        )
    ) {
        return 'Team 1 overs must be written like 0, 0.2, 15, or 19.5.';
    }

    if (
        !isValidCricketOvers(
            team2Overs
        )
    ) {
        return 'Team 2 overs must be written like 0, 0.3, 15, or 19.5.';
    }

    return '';
}


/* =========================================================
   PREVENT UNNECESSARY DUPLICATE SAVES
   ========================================================= */

function buildLiveSnapshot() {
    if (!matchForm) {
        return '';
    }

    const formData =
        new FormData(matchForm);

    const keys = [
        'match_id',
        'team1_id',
        'team2_id',
        'match_date',
        'match_state',
        'team1_score',
        'team1_wickets',
        'team1_overs',
        'team2_score',
        'team2_wickets',
        'team2_overs'
    ];

    return JSON.stringify(
        keys.map((key) => [
            key,
            String(
                formData.get(key) ?? ''
            )
        ])
    );
}


/* =========================================================
   UPDATE SCORE AND RESULT IN CURRENT PAGE
   ========================================================= */

function updateLiveElements(match) {
    const matchId =
        Number.parseInt(
            String(match.match_id),
            10
        );

    if (
        Number.isNaN(matchId) ||
        matchId <= 0
    ) {
        return;
    }

    document
        .querySelectorAll(
            `[data-live-score-id="${matchId}"]`
        )
        .forEach((element) => {
            element.textContent =
                match.score;
        });

    document
        .querySelectorAll(
            `[data-live-result-id="${matchId}"]`
        )
        .forEach((element) => {
            element.textContent =
                match.result_text;

            element.className =
                `badge ${match.result_class}`;
        });
}


/* =========================================================
   SAVE LIVE MATCH THROUGH AJAX
   ========================================================= */

async function saveLiveMatchNow() {
    if (
        !matchForm ||
        !isLiveMatchForm()
    ) {
        return;
    }

    /*
     * আগের request এখনো চললে
     * নতুন value queue-তে থাকবে।
     */
    if (liveSaveInProgress) {
        liveSaveQueued = true;
        return;
    }

    const validationError =
        validateLiveFormBeforeSave();

    if (validationError) {
        setLiveSaveStatus(
            validationError,
            'error'
        );

        return;
    }

    const snapshot =
        buildLiveSnapshot();

    /*
     * একই value বারবার database-এ
     * save করা হবে না।
     */
    if (
        snapshot ===
        lastSavedSnapshot
    ) {
        return;
    }

    liveSaveInProgress = true;
    liveSaveQueued = false;

    setLiveSaveStatus(
        'Saving live score...',
        'saving'
    );

    try {
        const formData =
            new FormData(matchForm);

        const response = await fetch(
            'api/live_match_update.php',
            {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            }
        );

        let result;

        try {
            result =
                await response.json();
        } catch {
            throw new Error(
                'The server returned an invalid response.'
            );
        }

        if (
            !response.ok ||
            !result.success
        ) {
            throw new Error(
                result.message ||
                'Live score could not be saved.'
            );
        }

        /*
         * New live match automatically
         * insert হলে নতুন match ID বসবে।
         */
        if (
            matchIdInput &&
            result.match_id
        ) {
            matchIdInput.value =
                String(result.match_id);
        }

        /*
         * Browser URL-এ edit ID বসানো হবে।
         * Page reload হবে না।
         */
        if (
            result.created &&
            result.match_id
        ) {
            const newUrl =
                new URL(
                    window.location.href
                );

            newUrl.searchParams.set(
                'edit',
                String(result.match_id)
            );

            window.history.replaceState(
                {},
                '',
                newUrl
            );
        }

        updateLiveElements({
            match_id: result.match_id,
            score: result.score,
            result_text:
                result.result_text,
            result_class:
                result.result_class
        });

        /*
         * match_id change হওয়ার পর
         * latest snapshot তৈরি করতে হবে।
         */
        lastSavedSnapshot =
            buildLiveSnapshot();

        const savedTime =
            new Date().toLocaleTimeString(
                [],
                {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                }
            );

        setLiveSaveStatus(
            `Live score saved automatically at ${savedTime}.`,
            'saved'
        );

        /*
         * New match create হলে button
         * automatically hide হবে।
         */
        updateMatchScoreFields();
    } catch (error) {
        console.error(error);

        setLiveSaveStatus(
            error instanceof Error
                ? error.message
                : 'Live score could not be saved.',
            'error'
        );
    } finally {
        liveSaveInProgress = false;

        /*
         * Saving চলাকালীন আরেকটি
         * value change হলে আবার save করবে।
         */
        if (liveSaveQueued) {
            liveSaveQueued = false;

            window.setTimeout(
                saveLiveMatchNow,
                100
            );
        }
    }
}


/* =========================================================
   AUTO-SAVE DEBOUNCE
   ========================================================= */

function scheduleLiveSave() {
    if (!isLiveMatchForm()) {
        return;
    }

    window.clearTimeout(
        liveSaveTimer
    );

    setLiveSaveStatus(
        'Waiting to save the latest value...',
        'saving'
    );

    /*
     * User typing শেষ করার 600ms পরে
     * database update হবে।
     */
    liveSaveTimer =
        window.setTimeout(() => {
            saveLiveMatchNow();
        }, 600);
}


/* =========================================================
   MATCH STATUS CHANGE EVENT
   ========================================================= */

if (
    matchStateSelect &&
    inningsFields
) {
    matchStateSelect.addEventListener(
        'change',
        () => {
            updateMatchScoreFields();

            /*
             * Existing match Pending/Completed
             * থেকে Live করলে status-ও save হবে।
             */
            if (
                isLiveMatchForm() &&
                currentMatchId() > 0
            ) {
                scheduleLiveSave();
            }
        }
    );

    updateMatchScoreFields();
}


/* =========================================================
   SCORE FIELD INPUT EVENTS
   ========================================================= */

liveScoreFields.forEach((field) => {
    /*
     * Keyboard দিয়ে value লিখলেই
     * auto-save schedule হবে।
     */
    field.addEventListener(
        'input',
        scheduleLiveSave
    );

    /*
     * Spinner, paste বা mobile input-এর
     * জন্য change event-ও রাখা হয়েছে।
     */
    field.addEventListener(
        'change',
        scheduleLiveSave
    );
});


/* =========================================================
   TEAM OR DATE CHANGE DURING LIVE MATCH
   ========================================================= */

[
    'team1_id',
    'team2_id',
    'match_date'
].forEach((id) => {
    const field =
        document.getElementById(id);

    field?.addEventListener(
        'change',
        () => {
            if (
                isLiveMatchForm() &&
                currentMatchId() > 0
            ) {
                scheduleLiveSave();
            }
        }
    );
});


/* =========================================================
   PREVENT ENTER KEY FROM NORMAL SUBMITTING LIVE MATCH
   ========================================================= */

if (matchForm) {
    matchForm.addEventListener(
        'submit',
        (event) => {
            /*
             * Existing Live match হলে
             * normal page submit বন্ধ থাকবে।
             */
            if (
                isLiveMatchForm() &&
                currentMatchId() > 0
            ) {
                event.preventDefault();

                window.clearTimeout(
                    liveSaveTimer
                );

                saveLiveMatchNow();
            }
        }
    );
}


/* =========================================================
   REAL-TIME SCORE POLLING
   ========================================================= */

let liveScorePollingInProgress =
    false;

async function refreshLiveScores() {
    /*
     * একই সময়ে দুইটি polling request
     * চালানো হবে না।
     */
    if (
        liveScorePollingInProgress ||
        document.hidden ||
        !document.querySelector(
            '[data-live-score-id]'
        )
    ) {
        return;
    }

    liveScorePollingInProgress = true;

    try {
        const response = await fetch(
            'api/live_scores.php',
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            }
        );

        if (!response.ok) {
            throw new Error(
                'Live scores could not be loaded.'
            );
        }

        const result =
            await response.json();

        if (
            !result.success ||
            !Array.isArray(
                result.matches
            )
        ) {
            throw new Error(
                result.message ||
                'Invalid live score response.'
            );
        }

        result.matches.forEach(
            updateLiveElements
        );
    } catch (error) {
        console.error(error);
    } finally {
        liveScorePollingInProgress =
            false;
    }
}


/* =========================================================
   START POLLING EVERY 2 SECONDS
   ========================================================= */

if (
    document.querySelector(
        '[data-live-score-id]'
    )
) {
    refreshLiveScores();

    window.setInterval(
        refreshLiveScores,
        2000
    );
}