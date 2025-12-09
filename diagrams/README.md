# Architecture Diagrams

This directory contains PNG images of the Document Gallery architecture diagrams for use in social media posts, presentations, and documentation.

## Diagram Files

All diagrams are provided in high-resolution PNG format (2400x1600 or 2400x1800 pixels), suitable for social media and presentations.

### 1. High-Level Architecture
**File:** `1-high-level-architecture.png`  
**Dimensions:** 2400 x 1600 px  
**Description:** Shows the overall plugin architecture including WordPress integration, core components, thumbnail generation system, admin interface, Block Editor integration, and external services.

### 2. Component Relationships
**File:** `2-component-relationships.png`  
**Dimensions:** 2400 x 1600 px  
**Description:** Class diagram showing all core classes, their methods, and relationships. Highlights the strategy pattern used for thumbnail generation with 5 different thumber implementations.

### 3. Gallery Rendering Flow
**File:** `3-gallery-rendering-flow.png`  
**Dimensions:** 2400 x 1800 px  
**Description:** Sequence diagram showing the complete flow from user viewing a page with `[dg]` shortcode to the final rendered gallery output, including thumbnail caching logic.

### 4. Thumbnail Generation Flow
**File:** `4-thumbnail-generation-flow.png`  
**Dimensions:** 2400 x 1800 px  
**Description:** Decision tree flowchart showing how the plugin selects the best available thumbnail generation method (Imagick → Ghostscript → AudioVideo → Thumber.co → Default).

### 5. Block Editor Integration
**File:** `5-block-editor-integration.png`  
**Dimensions:** 2400 x 1800 px  
**Description:** Sequence diagram showing how the Document Gallery block integrates with the WordPress Block Editor (Gutenberg), from insertion through configuration to frontend rendering.

## Source Diagrams

The source diagrams are written in Mermaid format and are embedded in the `ARCHITECTURE.md` file in the repository root. They can be viewed interactively on GitHub, which has native Mermaid support.

## Usage

These PNG files are:
- ✅ Optimized for social media (LinkedIn, Twitter, Facebook)
- ✅ Suitable for presentations and slideshows
- ✅ High-resolution for printing
- ✅ Clear text representation of architecture concepts

For interactive diagrams with pan/zoom capabilities, refer to the Mermaid diagrams in `ARCHITECTURE.md`.

## Image Specifications

- **Format:** PNG (Portable Network Graphics)
- **Color Mode:** RGB
- **Resolution:** 2400x1600 or 2400x1800 pixels
- **File Size:** ~110-140 KB each
- **Background:** White
- **Font:** DejaVu Sans (professional, readable)

## Notes

These are text-based diagram representations created to work in environments where interactive Mermaid rendering may not be available. They provide clear, readable architecture documentation suitable for sharing and embedding in various contexts.
