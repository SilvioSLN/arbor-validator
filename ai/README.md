# Arbor Validator — AI Agent Knowledge Base

Welcome to the AI context layer of **Arbor Validator** (`silviosln/arbor-validator`).

This directory contains structured documentation designed specifically to provide autonomous AI coding agents (and human engineers) with complete, verified architectural context, API contracts, workflows, patterns, and troubleshooting guides.

---

## Document Index

| Document | Purpose & Content |
| :--- | :--- |
| [`overview.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/overview.md) | Mission, problem solved, what Arbor Validator IS and what it IS NOT, design principles. |
| [`architecture.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/architecture.md) | Execution lifecycle, core layers (`ClassMapper`, `Coercer`, `ValidationContext`, `ErrorBag`). |
| [`api.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/api.md) | Exhaustive reference of every public class, interface, attribute, schema, and method. |
| [`workflows.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/workflows.md) | Task-oriented implementation recipes (DTOs, file uploads, lists, routers, i18n). |
| [`configuration.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/configuration.md) | i18n setup, custom error messages, testing mode, and custom rule development. |
| [`errors.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/errors.md) | Error bag structure, dotted path conventions (`address.street`), exception vs safe handling. |
| [`patterns.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/patterns.md) | Idiomatic design patterns, DDD integration, sanitization pipelines, clean code examples. |
| [`anti-patterns.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/anti-patterns.md) | Comprehensive catalog of mistakes and misconceptions to avoid. |
| [`troubleshooting.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/troubleshooting.md) | Solutions for common runtime, testing, formatting, and file upload issues. |
| [`examples.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/examples.md) | Copy-pasteable real-world scenarios and integration scripts. |
| [`reference.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/reference.md) | Fast lookup cheat-sheet of all attributes, rules, and schema methods. |
| [`reference.json`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/reference.json) | Machine-readable structured JSON catalog of the entire library API. |

---

## How an AI Agent Should Consume This Knowledge

1. **Before writing code**: Read [`AGENTS.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/AGENTS.md) at the repository root for the core decision tree.
2. **When implementing a specific task**: Consult [`workflows.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/workflows.md) for step-by-step guidance.
3. **When looking up signatures & parameters**: Consult [`api.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/api.md) or [`reference.json`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/reference.json).
4. **When debugging an unexpected result**: Consult [`errors.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/errors.md) and [`troubleshooting.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/troubleshooting.md).
