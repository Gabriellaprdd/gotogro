# GotoGro — Members Record Management System

## Overview

GotoGro is a web-based system for managing everyday grocery store tasks. Staff can keep track of members, products, stock levels, sales, and reports without having to manage everything separately.

This project focused on replacing manual-record-keeping with a more organized system while giving me hands-on experience with PHP, database management, and collaborative development.

---

# System Architecture

GotoGro uses a PHP and MySQL-based architecture, with JavaScript handling frontend interactions and data visualisation.

## 1. Frontend — HTML, CSS & JavaScript

The interface contains separate pages for inventory, members, sales, purchase history, analytics, and reports.

JavaScript handles form interactions, filtering, and fetching chart data, while PHP handles most of the page rendering.

## 2. Backend — PHP

PHP manages communication between the frontend and database.

Main responsibilities:

- Processing form submissions
- Handling staff authentication and sessions
- Managing members and products
- Recording sales transactions
- Validating user input
- Generating reports

## 3. Database — TiDB Cloud

The system uses a MySQL-compatible database hosted on TiDB Cloud to store store-related information.

The database contains five main tables:

### Staff

Stores staff account information used for login.

### Member

Stores customer membership details.

### Product

Stores product information, pricing, categories, and stock quantities.

### Sales Transactions

Stores the main details of each purchase.

### Transaction Item

Stores individual products purchased in each transaction.

Separating transactions from their items allows a single purchase to contain multiple products while keeping sales records organized.

---

# Design Decisions

## Why use PHP and MySQLi?

I chose PHP and MySQLi to work directly with server-side processing and database queries without relying on a framework.

This made it easier to understand how form submissions, sessions, and database operations work together.

The downside is that some logic is repeated across different pages, which could be improved by using reusable backend components.

## Why use database transactions?

Recording a sale involves several operations, including storing purchased items, calculating totals, and updating inventory.

To avoid incomplete records, these operations are handled within a database transaction.

If something goes wrong, such as insufficient stock or an invalid product, the transaction is rolled back instead of saving only part of the purchase.

## Why use TiDB Cloud and Docker?

TiDB Cloud was used to host the database, while Docker provides a consistent PHP and Apache environment.

This makes deployment more manageable, although the system still depends on a working database connection and correctly configured credentials.

---

# Main Features

## Member Management

Staff can add, edit, and remove member records, as well as view their purchase history.

## Inventory Management

The inventory system supports:

- Adding and deleting products
- Restocking existing products
- Filtering products by category
- Viewing low-stock notifications

Products with fewer than 20 units remaining are flagged as low stock.

## Sales Transactions

Staff can record purchases containing multiple products.

The system checks member information, validates product quantities, calculates totals, and updates inventory after a successful purchase.

## Data Analytics

The analytics dashboard uses Google Charts to display information such as:

- Total sales
- Units sold
- New members
- Popular products
- Payment methods
- Sales trends

## Report Generation

Staff can generate CSV reports by choosing a data category, columns, and date range.

---

# Development Challenges

## 1. Keeping Sales and Inventory Consistent

One of the trickier parts was managing several database changes whenever a sale was recorded.

The system needed to save transaction details and update stock without leaving incomplete records.

Using database transactions helped keep these operations together.

## 2. Handling Product IDs

Products use category-based IDs in the interface, while the database stores numeric IDs.

The sales handler needed additional logic to match these formats correctly, especially when retrieving products from different categories.

## 3. Managing Historical Sales Data

One limitation is that deleting a product also removes its related transaction-item records.

While this handles the database relationship, it can affect older sales reports.

Archiving products instead of permanently deleting them would be a better approach.

---

## Testing and Results

I used **Katalon Studio** for authentication testing and carried out functional testing on the main system features.

### Staff Authentication

Tested valid and invalid logins, unauthorised access, account lockout, and concurrent logins.

**Results:**
- 99.5% successful login rate
- 0% successful unauthorised logins
- 1.2% false rejection rate
- Successfully handled 8 concurrent login instances

### Member and Inventory Management

Tested:
- Adding, editing, and deleting member records
- Form validation and member search
- Product and stock updates
- Low-stock notifications

### Sales and Reports

Tested:
- Recording sales transactions
- Stock updates after purchases
- Report generation and analytics

### Code Validation

All 23 PHP files passed syntax validation using `php -l`.

---

# Technical Skills Gained

Through this project, I gained experience in:

- PHP backend development
- MySQL database integration
- CRUD operations
- Session-based authentication
- Database transactions
- Inventory and sales management
- Data visualisation
- CSV report generation
- Docker deployment

---

# Lessons Learned

This project taught me that handling sales involves more than just saving records. Stock levels, transaction details, and totals all need to stay consistent.

I also learned how important database design is, especially when changes like deleting products can affect other parts of the system.

---

# Future Improvements

- Archive products instead of deleting sales history
- Improve handling of simultaneous stock updates
- Add automated testing
- Reduce repeated PHP code
- Make low-stock thresholds configurable
- Improve user access controls

---

# Technologies Used

- PHP 8.4
- HTML
- CSS
- JavaScript
- MySQLi
- TiDB Cloud
- Google Charts
- Apache
- Docker
