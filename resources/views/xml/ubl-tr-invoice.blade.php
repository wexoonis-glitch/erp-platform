<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
    <cbc:ProfileID>{{ $profileId }}</cbc:ProfileID>
    <cbc:ID>{{ $invoice->invoice_no }}</cbc:ID>
    <cbc:CopyIndicator>false</cbc:CopyIndicator>
    <cbc:UUID>{{ $invoice->ettn }}</cbc:UUID>
    <cbc:IssueDate>{{ $issueDate }}</cbc:IssueDate>
    <cbc:IssueTime>{{ $issueTime }}</cbc:IssueTime>
    <cbc:InvoiceTypeCode>{{ $invoiceTypeCode }}</cbc:InvoiceTypeCode>
    @if(!empty($invoice->notes))
        <cbc:Note>{{ $invoice->notes }}</cbc:Note>
    @endif
    <cbc:DocumentCurrencyCode>{{ $currency }}</cbc:DocumentCurrencyCode>
    <cbc:LineCountNumeric>{{ $lineCount }}</cbc:LineCountNumeric>

    {{-- SATICI --}}
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="VKN">{{ $profile->tax_no }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name>{{ $profile->legal_name }}</cbc:Name>
            </cac:PartyName>
            <cac:PostalAddress>
                <cbc:StreetName>{{ $profile->address ?: '-' }}</cbc:StreetName>
                <cbc:BuildingNumber>-</cbc:BuildingNumber>
                <cbc:CitySubdivisionName>{{ $supplierTown }}</cbc:CitySubdivisionName>
                <cbc:CityName>{{ $supplierCity }}</cbc:CityName>
                <cbc:PostalZone>{{ $profile->postal_code ?: '-' }}</cbc:PostalZone>
                <cac:Country>
                    <cbc:Name>{{ $profile->country ?: 'Türkiye' }}</cbc:Name>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:TaxScheme>
                    <cbc:Name>{{ $profile->tax_office ?: '-' }}</cbc:Name>
                </cbc:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:Contact>
                <cbc:Telephone>{{ $profile->phone ?: '-' }}</cbc:Telephone>
                <cbc:ElectronicMail>{{ $profile->email ?: '-' }}</cbc:ElectronicMail>
            </cac:Contact>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>{{ $profile->legal_name }}</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>

    {{-- ALICI --}}
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="{{ strlen($customer->tax_no) === 11 ? 'TCKN' : 'VKN' }}">{{ $customer->tax_no }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name>{{ $customer->name }}</cbc:Name>
            </cac:PartyName>
            <cac:PostalAddress>
                <cbc:StreetName>{{ $customer->address ?: '-' }}</cbc:StreetName>
                <cbc:BuildingNumber>-</cbc:BuildingNumber>
                <cbc:CitySubdivisionName>{{ $customerTown }}</cbc:CitySubdivisionName>
                <cbc:CityName>{{ $customerCity }}</cbc:CityName>
                <cbc:PostalZone>-</cbc:PostalZone>
                <cac:Country>
                    <cbc:Name>Türkiye</cbc:Name>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:TaxScheme>
                    <cbc:Name>{{ $customer->tax_office ?: '-' }}</cbc:Name>
                </cbc:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:Contact>
                <cbc:Telephone>{{ $customer->phone ?: '-' }}</cbc:Telephone>
                <cbc:ElectronicMail>{{ $customer->email ?: '-' }}</cbc:ElectronicMail>
            </cac:Contact>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>{{ $customer->name }}</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>

    {{-- FATURA TOPLAM KDV --}}
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="{{ $currency }}">{{ number_format((float) $invoice->vat_total, 2, '.', '') }}</cbc:TaxAmount>
        @php
            // KDV oranlarına göre grupla
            $vatGroups = $invoice->lines->groupBy(fn($l) => (string) $l->vat_rate);
        @endphp
        @foreach($vatGroups as $rate => $lines)
            @php
                $taxable = $lines->sum(fn($l) => (float) $l->subtotal);
                $taxAmt  = $lines->sum(fn($l) => (float) $l->vat_amount);
            @endphp
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="{{ $currency }}">{{ number_format($taxable, 2, '.', '') }}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="{{ $currency }}">{{ number_format($taxAmt, 2, '.', '') }}</cbc:TaxAmount>
                <cbc:Percent>{{ number_format((float) $rate, 2, '.', '') }}</cbc:Percent>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:Name>KDV</cbc:Name>
                        <cbc:TaxTypeCode>0015</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endforeach
    </cac:TaxTotal>

    {{-- YASAL TOPLAM --}}
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="{{ $currency }}">{{ number_format((float) $invoice->subtotal, 2, '.', '') }}</cbc:LineExtensionAmount>
        <cbc:TaxExclusiveAmount currencyID="{{ $currency }}">{{ number_format((float) $invoice->subtotal, 2, '.', '') }}</cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="{{ $currency }}">{{ number_format((float) $invoice->grand_total, 2, '.', '') }}</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID="{{ $currency }}">{{ number_format((float) $invoice->grand_total - (float) $invoice->paid_total, 2, '.', '') }}</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>

    {{-- SATIRLAR --}}
    @foreach($invoice->lines as $line)
        <cac:InvoiceLine>
            <cbc:ID>{{ $line->line_no }}</cbc:ID>
            <cbc:InvoicedQuantity unitCode="{{ $unitMapper($line->product?->unit) }}">{{ number_format((float) $line->quantity, 2, '.', '') }}</cbc:InvoicedQuantity>
            <cbc:LineExtensionAmount currencyID="{{ $currency }}">{{ number_format((float) $line->subtotal, 2, '.', '') }}</cbc:LineExtensionAmount>

            <cac:TaxTotal>
                <cbc:TaxAmount currencyID="{{ $currency }}">{{ number_format((float) $line->vat_amount, 2, '.', '') }}</cbc:TaxAmount>
                <cac:TaxSubtotal>
                    <cbc:TaxableAmount currencyID="{{ $currency }}">{{ number_format((float) $line->subtotal, 2, '.', '') }}</cbc:TaxableAmount>
                    <cbc:TaxAmount currencyID="{{ $currency }}">{{ number_format((float) $line->vat_amount, 2, '.', '') }}</cbc:TaxAmount>
                    <cbc:Percent>{{ number_format((float) $line->vat_rate, 2, '.', '') }}</cbc:Percent>
                    <cac:TaxCategory>
                        <cac:TaxScheme>
                            <cbc:Name>KDV</cbc:Name>
                            <cbc:TaxTypeCode>0015</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>
            </cac:TaxTotal>

            <cac:Item>
                <cbc:Name>{{ $line->description }}</cbc:Name>
                @if($line->product && $line->product->code)
                    <cbc:Description>{{ $line->product->code }} - {{ $line->product->name }}</cbc:Description>
                    <cac:SellersItemIdentification>
                        <cbc:ID>{{ $line->product->code }}</cbc:ID>
                    </cac:SellersItemIdentification>
                @endif
            </cac:Item>

            <cac:Price>
                <cbc:PriceAmount currencyID="{{ $currency }}">{{ number_format((float) $line->unit_price, 2, '.', '') }}</cbc:PriceAmount>
            </cac:Price>
        </cac:InvoiceLine>
    @endforeach
</Invoice>
