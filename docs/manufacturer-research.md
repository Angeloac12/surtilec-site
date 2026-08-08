# Manufacturer Research

Last verified: 2026-08-03.

The product batch uses only official manufacturer sources. A product is considered verified only when the published conductor count, AWG size and construction match exactly. A similar family is not enough to assign a manufacturer reference.

## Verified families

- **Centelsa by Nexans:** the official Colombia product page publishes THHN/THWN-2 600 V products in 12 AWG and 14 AWG, with references 200303 and 200300, copper conductor, PVC insulation, nylon cover, UL 83, NTC 1332 and 90 °C operation. Source: [official product page](https://www.nexans.co/es/products/Construcci%C3%B3n/Cables-de-Cobre-de-Baja-Tensi%C3%B3n-Aislados/Alambre-THHN-THWN-2/Wire-Type-24145.html).
- **Procables:** the official Termoflex MP sheet publishes exact codes for the imported 2/3/5-conductor configurations in the first batch. It documents 600 V, 90 °C, flexible copper, PVC/nylon construction and the listed UL/NTC certifications. Source: [official Termoflex MP sheet](https://co.prysmian.com/sites/co.prysmian.com/files/media/documents/FT%20TERMOFLEX%20MP_COL_1.pdf).

## Reviewed but not assigned

- **Southwire:** official TC-ER and THHN/THWN-2 families were reviewed. The published products do not match the pilot control compositions containing an additional 20 AWG conductor, so no Southwire reference is assigned to those rows. Sources: [SIMpull THHN/THWN-2](https://www.southwire.com/wire-cable/building-wire/simpull-sup-sup-thhn-thwn-2-copper/p/SPEC10000) and [14 AWG 9/C TC-ER](https://www.southwire.com/wire-cable/power-control/cu-600v-pvc-nylon-insulation-pvc-jacket-thhn-thwn-ct-rated-sunlight-resistant-for-direct-burial-silicone-free/p/40874099).
- **Belden:** official control products were reviewed, but the matching product families publish different conductor counts, voltage/rating or shielding from the pilot `+20 AWG` constructions. No Belden reference is assigned by resemblance. Example: [Belden M39058](https://www.belden.com/products/cable/electronic-wire-cable/multi-conductor-cable/m39058).
- **Celsa:** the official catalog reviewed is focused on electrical distribution equipment, protection, lighting and EV charging rather than these low-voltage cable families. Source: [official Celsa catalogs](https://www.celsa.com.co/catalogos-generales/).
- **Marwa:** the official site presents an importer/wholesaler portfolio centered on structured cabling, networking and fiber infrastructure; it does not provide an official manufacturer data sheet for the pilot electrical cables. Source: [official Marwa site](https://marwa.com.co/).
- **Argos:** the official Colombia site identifies Argos as a cement/concrete business; no official cable-manufacturer catalog was found. Source: [official Argos site](https://colombia.argos.co/).

Pending rows remain in `data/surtilec-pilot-manufacturer-crosswalk.csv`. They must not be imported as a brand or manufacturer reference until an official sheet publishes the exact construction.
