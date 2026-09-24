# HoursFlow — Business Hours CTA

## What It Is

HoursFlow CTA is a lightweight WordPress plugin that automatically changes a website's call-to-action based on whether the business is currently open or closed.

Instead of showing the same CTA all day, the site can show the most useful action for the current situation.

For example:

**During business hours**

> Call Now
> We're open until 6 PM

**Outside business hours**

> Book an Appointment
> We reopen tomorrow at 9 AM

---

## Why Build It

Many local-business websites use a fixed CTA such as:

- Call Now
- WhatsApp Us
- Book Appointment
- Contact Us

But the best action changes depending on the time.

A "Call Now" button is less useful when the business is closed. At that point, sending the visitor to a booking form, WhatsApp, or contact form may convert better.

HoursFlow CTA solves this automatically.

---

## Target Users

The plugin is mainly for businesses where opening hours matter, such as:

- Clinics
- Dentists
- Restaurants
- Salons
- Repair services
- Law firms
- Consultants
- Real-estate agents
- Local service businesses

It can also be useful for WordPress freelancers and agencies building websites for these businesses.

---

## Core Idea

The site owner defines business hours and two CTA states.

### When Open

Example:

- Label: `Call Now`
- URL/action: `tel:+123456789`
- Secondary text: `We're open until 6 PM`

### When Closed

Example:

- Label: `Book Appointment`
- URL/action: `/booking/`
- Secondary text: `We reopen tomorrow at 9 AM`

The plugin checks the configured timezone and automatically displays the correct CTA.

---

## MVP Features

The first version should stay small.

- Business hours for Monday–Sunday
- Business timezone
- Open CTA
- Closed CTA
- CTA label
- CTA URL/action
- Optional status text
- Shortcode
- Gutenberg block
- Basic styling controls
- Correct handling of days when the business is closed

Possible shortcode:

```text
[hoursflow_cta]
```

---

## What It Should Not Become

HoursFlow CTA should not become:

- A complete booking system
- A business-directory plugin
- A full business-hours plugin
- A popup builder
- A marketing automation platform
- A CRM

Its job should remain simple:

> Show the right CTA based on whether the business is open or closed.

---

## Possible Future Features

After the MVP is validated:

- Multiple opening periods per day
- Holiday and special-hours overrides
- Different CTAs for different days
- Multiple CTA profiles
- PHP/template function
- ButtonFlow integration
- WooCommerce integration
- Elementor or other builder integrations
- Schema/business-hours integration

---

## Positioning

Possible one-line description:

> Automatically show the right WordPress CTA based on your business hours.

Alternative:

> Show "Call Now" when you're open and "Book Online" when you're closed.

The second version is probably stronger because users immediately understand what the plugin does.

---

## Main Principle

HoursFlow CTA should follow the same philosophy as ButtonFlow:

**One specific problem, solved well, with very little overhead.**

No SaaS account, no external service, no tracking, and no unnecessary frontend weight.
