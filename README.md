# Cricket Tournament Management System

A PHP and MySQL application for managing a cricket tournament from team setup through match results, player scores, awards, and the points table.
|
[![Live Website](https://img.shields.io/badge/Live%20Website-Open%20Now-brightgreen)](https://crickettournament.free.nf/)

## Overview

This project is built with plain PHP, MySQL, HTML, CSS, and JavaScript. It supports three access levels:

- Super Admin: creates the first system account and can create admin accounts
- Admin: manages teams, players, matches, scores, awards, points, and user approvals
- User: views the public tournament dashboard and profile pages after approval

Match results are tracked with innings scores, wickets, overs, and a result status of `pending`, `live`, `completed`, or `draw`.

## Features

### Authentication and accounts

- One-time Super Admin setup
- Login, logout, and session-based access control
- Public user registration with approval workflow
- Admin account creation by Super Admin
- Profile editing and password change support
- CSRF protection and password hashing

### Tournament management

- Team management with captain details
- Player management with team assignment and role tracking
- Match scheduling between two teams
- Live and completed match score tracking
- Individual player runs and wickets entry
- Award type creation and match award assignment
- Automatic points table calculation and ranking

### Dashboards

- Admin dashboard with totals, recent matches, standings, top run scorers, and top wicket takers
- User dashboard with tournament summary, recent results, and current points table

### Client-side helpers

- Table search
- Delete confirmation prompts
- Responsive mobile menu
- Match-specific player loading through the API
- Live score polling support

## Pages and routes

| Page | Purpose |
|---|---|
| `setup_admin.php` | Create the first Super Admin account |
| `login.php` | Login for Super Admin, Admin, and User accounts |
| `register.php` | Public user registration |
| `index.php` | Admin dashboard |
| `user_dashboard.php` | User dashboard |
| `teams.php` | Team management |
| `players.php` | Player management |
| `matches.php` | Match and innings score management |
| `scores.php` | Individual player performance entry |
| `award_types.php` | Award type management |
| `awards.php` | Match award assignment |
| `points.php` | Points table |
| `profile.php` | Profile and password management |
| `users.php` | User approvals and Super Admin account creation |
| `logout.php` | Logout |

## API endpoints

| Endpoint | Purpose |
|---|---|
| `api/players_by_match.php` | Returns eligible players for a selected match |
| `api/live_scores.php` | Returns current match scores and result labels as JSON |
| `api/live_match_update.php` | Saves live match score updates as JSON |

## Database tables

The project expects these tables:

| Table | Purpose |
|---|---|
| `users` | Super Admin, Admin, and User accounts |
| `team` | Team details |
| `player` | Player details and team assignment |
| `matches` | Match schedule, innings scores, and result data |
| `score` | Individual player runs and wickets |
| `award_type` | Award categories |
| `match_award` | Award assignments |
| `points_table` | Calculated standings |

The `matches` table must support these result fields:

- `team1_score`
- `team2_score`
- `team1_wickets`
- `team2_wickets`
- `team1_overs`
- `team2_overs`
- `result_status`
- `winner_team_id`

The `points_table` table must support:

- `matches_played`
- `wins`
- `losses`
- `draws`
- `points`

## Technology stack

| Technology | Purpose |
|---|---|
| PHP 8.1+ | Server-side application logic |
| MySQL / MariaDB | Database storage |
| PDO | Database access |
| HTML5 | Markup |
| CSS3 | Styling |
| JavaScript | UI interactions and API calls |
| Apache | Local web server |

No framework, Composer package, or npm package is required.

## Requirements

- PHP 8.1 or later
- MySQL 5.7+ or MariaDB equivalent
- Apache or another PHP-capable web server
- PDO MySQL extension enabled
- Laragon, XAMPP, WAMP, or a similar local setup

## Setup

1. Clone or copy the project into your web root.

	Example for Laragon:

	```text
	C:\laragon\www\cricket_tournament_app
	```

2. Start Apache and MySQL.

3. Create a database named `cricket_tournament_db`.

4. Import the project schema if you have one, or create the tables listed above.

5. Update database credentials in `config/db.php`.

	Default values:

	```php
	$DB_HOST = 'localhost';
	$DB_NAME = 'cricket_tournament_db';
	$DB_USER = 'root';
	$DB_PASS = '';
	```

6. Open `setup_admin.php` in the browser and create the first Super Admin account.

7. Log in through `login.php`.

8. Register users through `register.php`, then approve them from `users.php`.

## Suggested workflow

1. Create the first Super Admin account.
2. Log in as Super Admin.
3. Create Admin accounts if needed.
4. Add teams.
5. Add players and assign them to teams.
6. Create award types.
7. Schedule matches.
8. Enter live or completed scores.
9. Record player runs, wickets, and match awards.
10. Review the points table and dashboards.

## Project structure

```text
cricket_tournament_app/
├── api/
│   ├── live_match_update.php
│   ├── live_scores.php
│   └── players_by_match.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── config/
│   ├── database.php
│   └── db.php
├── includes/
│   ├── bootstrap.php
│   ├── footer.php
│   ├── functions.php
│   └── header.php
├── award_types.php
├── awards.php
├── index.php
├── login.php
├── logout.php
├── matches.php
├── players.php
├── points.php
├── profile.php
├── register.php
├── scores.php
├── setup_admin.php
├── teams.php
├── user_dashboard.php
├── users.php
├── LICENSE
└── README.md
```

## Security notes

- PDO prepared statements are used throughout
- Emulated prepared statements are disabled
- CSRF tokens protect form submissions
- Passwords are hashed with `password_hash()`
- Output is escaped before rendering
- Login-required and admin-only pages are enforced on the server

## Troubleshooting

- If the database connection fails, verify `config/db.php` and confirm MySQL is running.
- If `setup_admin.php` does not open, make sure the project is served through `http://localhost/...`, not opened directly from File Explorer.
- If changes do not appear in the browser, do a hard refresh.

## License

This project is licensed under the MIT License. See `LICENSE` for details.

## Author

Mehedi Hasan