# Hirely documentation

Hirely is an AI-assisted recruitment and career-preparation platform. Public UI, `APP_NAME`, mail from-name, and seed accounts use **Hirely**.

This folder is the source of truth for:

- What the product is supposed to do
- What is implemented today
- What is partial, placeholder-only, or not started
- How the application and infrastructure are structured

## How to use these docs

| Document | Purpose |
| --- | --- |
| [PRODUCT_STATUS.md](./PRODUCT_STATUS.md) | Living tracker for the 23 product capabilities. Update this first when a feature changes. |
| [features.md](./features.md) | Detailed status for each capability, including sub-features, key files, and remaining work. |
| [architecture.md](./architecture.md) | System design, roles, request flow, and module map. |
| [application.md](./application.md) | Stack, modules, routes, and current application behaviour. |
| [infrastructure.md](./infrastructure.md) | Runtime, data stores, third-party services, CI, and environment. |
| [data-model.md](./data-model.md) | Database entities and relationships. |
| [backlog.md](./backlog.md) | Prioritized work to close the gap between current code and the product vision. |

## Status vocabulary

Use these labels consistently across all docs:

| Status | Meaning |
| --- | --- |
| **Implemented** | Backend and UI work for the current product scope. Gaps may still exist versus the full vision. |
| **Partial** | Real logic exists, but important parts of the intended capability are missing. |
| **Placeholder** | A page or UI shell exists; there is no working backend or live data. |
| **Not started** | No meaningful implementation. |

## How to keep this folder current

When you ship or change a capability:

1. Update the row in [PRODUCT_STATUS.md](./PRODUCT_STATUS.md).
2. Adjust the matching section in [features.md](./features.md).
3. If the change adds tables, services, or integrations, update [data-model.md](./data-model.md), [architecture.md](./architecture.md), or [infrastructure.md](./infrastructure.md).
4. Move or close items in [backlog.md](./backlog.md).

Last reviewed against the codebase: **16 August 2026**.
