/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

describe('Title configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);

        // Create form with a single "request type" question
        cy.createFormWithAPI({"name": "My form name"}).visitFormTab('Form');
        cy.findByRole('button', {'name': "Add a new question"}).click();
        cy.focused().type("What is your name ?");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Short answer');
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', {'name': "Items to create"}).click();
        cy.findByRole('button', {'name': "Add ticket"}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully added');
    });

    it('can configure the title field', () => {
        cy.findByRole('textbox', {'name': "Title"}).clear();
        cy.findByRole('textbox', {'name': "Title"}).type("My specific form name");
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.findByRole('textbox', {'name': "Title"}).should('have.value', "My specific form name");
    });

    it('can create ticket using default configuration', () => {
        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.findByRole('textbox', {'name': "What is your name ?"}).type("John doe");
        cy.findByRole('button', {'name': 'Send form'}).click();
        cy.findByRole('link', {'name': 'My form name'}).click();

        // Check ticket values, default name should be the form name
        cy.findByRole('heading').should('contain.text', 'My form name');

        // Others possibles configurations are tested directly by the backend.
    });
});
