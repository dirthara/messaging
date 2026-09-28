# Security Policy

## Supported versions

| Version | Status |
| --- | --- |
| 0.1.x | Active development; unreleased |
| Older | Unsupported |

While the package is pre-1.0, only the latest release line receives fixes.

## Reporting a vulnerability

Report vulnerabilities privately using GitHub's
[Report a vulnerability](https://github.com/dirthara/messaging/security/advisories/new)
form. Do not disclose vulnerabilities in public issues or pull requests.

Include the affected version or commit, PHP version, a minimal reproduction,
and the impact and conditions needed to trigger the issue. Maintainers will
acknowledge and assess the report. Confirmed fixes are published with an
advisory crediting the reporter unless they prefer otherwise.

## Scope

The package defines one-way message publishing, routes each message type to one publisher, and records published
messages in a test fake. In scope are flaws in that behaviour and in the package's development configuration, such as:

- a route accepted for a type no message can have, or a second route silently replacing the first;
- a message routed to a publisher other than the one registered for its exact class, or discarded instead of refused;
- a message published more than once, or altered on its way to the routed publisher;
- an exception message or context disclosing a message's contents, or letting a message type forge a log line.

Out of scope: this package has no queue, worker, broker, remote transport, message serialisation, or retry
infrastructure. Security issues in those, and in the publishers other packages provide, belong to the package or
implementation that provides them.

Bugs in PHP or third-party dependencies should also be reported upstream.
Application code and the sensitivity of data an application chooses to store
are the application's responsibility.
