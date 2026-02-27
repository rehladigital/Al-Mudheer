# RBAC Logic (Version 2.10)

Al Mudheer uses a two-layer RBAC model:

1. **System Permission Role (core access engine)**  
   Controls platform-level authorization checks.
2. **Business Role (organization model)**  
   Controls business ownership structure and business rules.

## A) System Permission Roles (existing core roles)

| System role | Level | Core capability |
|---|---:|---|
| `readonly` | 5 | View-only access |
| `commenter` | 10 | View and comments |
| `editor` | 20 | Create and update work items |
| `manager` | 30 | Manage projects/teams in allowed scope |
| `admin` | 40 | Full administration except owner-only controls |
| `owner` | 50 | Full platform ownership and critical settings |

## B) Business Roles (organization layer)

| Business role | Purpose | Key business rule |
|---|---|---|
| `Owner` | Highest business authority | Global access across clients/departments |
| `Company Manager` | Company-wide operational manager | Can operate across departments/clients based on assignment |
| `Department Manager` | Department-scoped manager | Can create projects only for assigned clients and own department scope |

## C) Mapping Rules

| Entity | Rule |
|---|---|
| User -> Business Role | Exactly one business role per user |
| User -> Clients | One user can be assigned to multiple clients |
| Client -> Departments | One client can be linked to one or many departments |
| Project creation (`Department Manager`) | Allowed only when target client and department are both in manager assignments |

## D) Why Two Layers

| Layer | Why it exists |
|---|---|
| System Permission Role | Keeps core authorization stable and backward-compatible |
| Business Role | Models organization structure without breaking core auth checks |
