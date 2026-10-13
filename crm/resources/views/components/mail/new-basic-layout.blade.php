@props(['data' => [], 'message' => null])

@php
    $siteName = (string) ($data['site_name'] ?? get_site_name() ?? config('app.name'));
    $logoUrl = (string) ($data['logo_url'] ?? asset('images/logo.png'));
@endphp

<!doctype>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    :root {
        --dl-layout-size-large: 144px;
        --dl-layout-size-small: 48px;
        --dl-layout-space-unit: 16px;
        --dl-color-default-text: rgba(0, 19, 19, 1);
        --dl-layout-size-medium: 96px;
        --dl-layout-size-xlarge: 192px;
        --dl-layout-size-xsmall: 16px;
        --dl-color-theme-accent1: #FFFFFF;
        --dl-color-theme-accent2: #F5D1B0;
        --dl-layout-radius-round: 50%;
        --dl-layout-size-xxlarge: 288px;
        --dl-color-default-accent: rgba(2, 204, 204, 1);
        --dl-color-theme-primary1: #BF4408;
        --dl-color-theme-primary2: #E65103;
        --dl-layout-size-maxwidth: 1400px;
        --dl-color-default-primary: rgba(14, 159, 100, 1);
        --dl-color-default-sectext: rgba(49, 49, 49, 1);
        --dl-layout-radius-radius2: 2px;
        --dl-layout-radius-radius4: 4px;
        --dl-layout-radius-radius8: 8px;
        --dl-layout-space-halfunit: 8px;
        --dl-layout-space-sixunits: 96px;
        --dl-layout-space-twounits: 32px;
        --dl-color-theme-secondary1: #FFFFFF;
        --dl-color-theme-secondary2: #FBF1EB;
        --dl-layout-space-fiveunits: 80px;
        --dl-layout-space-fourunits: 64px;
        --dl-layout-space-threeunits: 48px;
        --dl-color-default-background: rgba(250, 255, 253, 1);
        --dl-color-theme-neutral-dark: #191818;
        --dl-layout-radius-cardradius: 8px;
        --dl-color-theme-neutral-light: #FBFAF9;
        --dl-layout-radius-imageradius: 8px;
        --dl-layout-radius-inputradius: 24px;
        --dl-layout-radius-buttonradius: 24px;
        --dl-layout-space-oneandhalfunits: 24px;
    }

    .button {
        color: var(--dl-color-theme-neutral-dark);
        display: inline-block;
        padding: 0.5rem 1rem;
        border-color: var(--dl-color-theme-neutral-dark);
        border-width: 1px;
        border-radius: 4px;
        background-color: var(--dl-color-theme-neutral-light);
    }

    .input {
        color: var(--dl-color-theme-neutral-dark);
        cursor: auto;
        padding: 0.5rem 1rem;
        border-color: var(--dl-color-theme-neutral-dark);
        border-width: 1px;
        border-radius: 4px;
        background-color: var(--dl-color-theme-neutral-light);
    }

    .textarea {
        color: var(--dl-color-theme-neutral-dark);
        cursor: auto;
        padding: 0.5rem;
        border-color: var(--dl-color-theme-neutral-dark);
        border-width: 1px;
        border-radius: 4px;
        background-color: var(--dl-color-theme-neutral-light);
    }

    .list {
        width: 100%;
        margin: 1em 0px 1em 0px;
        display: block;
        padding: 0px 0px 0px 1.5rem;
        list-style-type: none;
        list-style-position: outside;
    }

    .list-item {
        display: list-item;
    }

    .teleport-show {
        display: flex !important;
        transform: none !important;
    }

    .thq-input {
        color: var(--dl-color-theme-neutral-dark);
        cursor: auto;
        outline: none;
        padding: 0.5rem 1rem;
        align-self: stretch;
        text-align: center;
        border-color: var(--dl-color-theme-neutral-dark);
        border-width: 1px;
        border-radius: var(--dl-layout-radius-inputradius);
        background-color: var(--dl-color-theme-neutral-light);
    }

    .thq-input:focus {
        outline: 1px solid var(--dl-color-theme-primary1);
    }

    .thq-button-filled {
        gap: var(--dl-layout-space-halfunit);
        fill: var(--dl-color-theme-secondary1);
        color: var(--dl-color-theme-secondary1);
        cursor: pointer;
        display: flex;
        transition: 0.3s;
        align-items: center;
        font-weight: bold;
        padding-top: var(--dl-layout-space-halfunit);
        white-space: nowrap;
        border-color: var(--dl-color-theme-primary1);
        border-width: 1px;
        padding-left: var(--dl-layout-space-oneandhalfunits);
        border-radius: var(--dl-layout-radius-buttonradius);
        padding-right: var(--dl-layout-space-oneandhalfunits);
        padding-bottom: var(--dl-layout-space-halfunit);
        justify-content: center;
        background-color: var(--dl-color-theme-primary1);
    }

    .thq-button-filled:hover {
        fill: var(--dl-color-theme-secondary2);
        color: var(--dl-color-theme-secondary2);
        border-color: var(--dl-color-theme-primary2);
        background-color: var(--dl-color-theme-primary2);
    }

    .thq-button-outline {
        gap: var(--dl-layout-space-halfunit);
        fill: var(--dl-color-theme-primary1);
        color: var(--dl-color-theme-primary1);
        border: 1px solid;
        cursor: pointer;
        display: flex;
        transition: 0.3s;
        align-items: center;
        font-weight: bold;
        padding-top: var(--dl-layout-space-halfunit);
        white-space: nowrap;
        border-color: var(--dl-color-theme-primary1);
        padding-left: var(--dl-layout-space-oneandhalfunits);
        border-radius: var(--dl-layout-radius-buttonradius);
        padding-right: var(--dl-layout-space-oneandhalfunits);
        padding-bottom: var(--dl-layout-space-halfunit);
        justify-content: center;
    }

    .thq-button-outline:hover {
        fill: var(--dl-color-theme-secondary2);
        color: var(--dl-color-theme-secondary2);
        border-color: var(--dl-color-theme-primary2);
        background-color: var(--dl-color-theme-primary2);
    }

    .thq-button-flat {
        gap: var(--dl-layout-space-halfunit);
        fill: var(--dl-color-theme-primary1);
        color: var(--dl-color-theme-primary1);
        cursor: pointer;
        display: flex;
        transition: 0.3s;
        align-items: center;
        font-weight: bold;
        padding-top: var(--dl-layout-space-halfunit);
        white-space: nowrap;
        border-color: transparent;
        border-width: 1px;
        padding-left: var(--dl-layout-space-oneandhalfunits);
        border-radius: var(--dl-layout-radius-buttonradius);
        padding-right: var(--dl-layout-space-oneandhalfunits);
        padding-bottom: var(--dl-layout-space-halfunit);
        justify-content: center;
    }

    .thq-button-flat:hover {
        fill: var(--dl-color-theme-secondary1);
        color: var(--dl-color-theme-secondary1);
        border-color: var(--dl-color-theme-primary2);
        background-color: var(--dl-color-theme-primary2);
    }

    .thq-heading-1 {
        font-size: 48px;
        font-family: STIX Two Text;
        font-weight: 700;
        line-height: 1.5;
    }

    .thq-heading-2 {
        font-size: 35px;
        font-family: STIX Two Text;
        font-weight: 600;
        line-height: 1.5;
    }

    .thq-heading-3 {
        font-size: 26px;
        font-family: STIX Two Text;
        font-weight: 600;
        line-height: 1.5;
    }

    .thq-body-large {
        font-size: 18px;
        font-family: Noto Sans;
        line-height: 1.5;
    }

    .thq-body-small {
        font-size: 16px;
        font-family: Noto Sans;
        line-height: 1.5;
    }

    .thq-team-image-round {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
    }

    .thq-section-padding {
        width: 100%;
        display: flex;
        padding: var(--dl-layout-space-fiveunits);
        position: relative;
        align-items: center;
        flex-direction: column;
    }

    .thq-section-max-width {
        width: 100%;
        max-width: var(--dl-layout-size-maxwidth);
    }

    .thq-img-ratio-1-1 {
        width: 100%;
        object-fit: cover;
        aspect-ratio: 1/1;
        border-radius: var(--dl-layout-radius-imageradius);
    }

    .thq-img-ratio-16-9 {
        width: 100%;
        object-fit: cover;
        aspect-ratio: 16/9;
        border-radius: var(--dl-layout-radius-imageradius);
    }

    .thq-img-ratio-4-3 {
        width: 100%;
        object-fit: cover;
        aspect-ratio: 4/3;
        border-radius: var(--dl-layout-radius-imageradius);
    }

    .thq-img-ratio-4-6 {
        width: 100%;
        object-fit: cover;
        aspect-ratio: 4/6;
        border-radius: var(--dl-layout-radius-imageradius);
    }

    .thq-img-round {
        width: 100%;
        border-radius: var(--dl-layout-radius-round);
    }

    .thq-flex-column {
        gap: var(--dl-layout-space-twounits);
        display: flex;
        overflow: hidden;
        position: relative;
        align-items: center;
        flex-direction: column;
    }

    .thq-flex-row {
        gap: var(--dl-layout-space-twounits);
        display: flex;
        overflow: hidden;
        position: relative;
        align-items: center;
    }

    .thq-grid-6 {
        display: grid;
        grid-gap: var(--dl-layout-space-twounits);
        grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr;
    }

    .thq-grid-5 {
        display: grid;
        grid-gap: var(--dl-layout-space-twounits);
        grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
    }

    .thq-card {
        gap: var(--dl-layout-space-oneandhalfunits);
        display: flex;
        padding: var(--dl-layout-space-twounits);
        align-items: stretch;
        border-radius: var(--dl-layout-radius-cardradius);
        flex-direction: column;
    }

    .thq-box-shadow {
        box-shadow: 0px 0px 5px -2px var(--dl-color-theme-neutral-dark);
    }

    .thq-grid-3 {
        display: grid;
        grid-gap: var(--dl-layout-space-twounits);
        grid-template-columns: 1fr 1fr 1fr;
    }

    .thq-grid-4 {
        display: grid;
        grid-gap: var(--dl-layout-space-twounits);
        grid-template-columns: 1fr 1fr 1fr 1fr;
    }

    .thq-grid-2 {
        width: 100%;
        display: grid;
        grid-gap: var(--dl-layout-space-twounits);
        grid-template-columns: 1fr 1fr;
    }

    .thq-checkbox {
        width: var(--dl-layout-size-xsmall);
        height: var(--dl-layout-size-xsmall);
    }

    .thq-select {
        cursor: pointer;
        appearance: none;
        padding-top: var(--dl-layout-space-halfunit);
        padding-left: var(--dl-layout-space-unit);
        border-radius: var(--dl-layout-radius-inputradius);
        padding-right: var(--dl-layout-space-twounits);
        padding-bottom: var(--dl-layout-space-halfunit);
        background-color: var(--dl-color-theme-neutral-light);
        background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg width%3D%2220%22 height%3D%2220%22 xmlns%3D%22http%3A//www.w3.org/2000/svg%22 viewBox%3D%220 0 20 20%22 fill%3D%22%23000%22%3E%3Cpath d%3D%22M4.293 7.293a1 1 0 011.414 0L10 11.586l4.293-4.293a1 1 0 111.414 1.414l-5 5a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414z%22/%3E%3C/svg%3E');
        background-repeat: no-repeat;
        background-position: right 8px center;
    }

    .thq-divider-horizontal {
        width: 100%;
        height: 1px;
        background-color: var(--dl-color-theme-neutral-dark);
    }

    .thq-icon-small {
        width: 24px;
        height: 24px;
    }

    .thq-button-icon {
        fill: var(--dl-color-theme-secondary1);
        padding: 3px;
        transition: 0.3s;
        border-radius: var(--dl-layout-radius-round);
    }

    .thq-button-icon:hover {
        fill: var(--dl-color-theme-secondary2);
    }

    .thq-icon-medium {
        width: var(--dl-layout-size-small);
        height: var(--dl-layout-size-small);
    }

    .thq-icon-x-small {
        width: var(--dl-layout-size-xsmall);
        height: var(--dl-layout-size-xsmall);
    }

    .thq-link {
        cursor: pointer;
        display: inline-block;
        overflow: hidden;
        background: linear-gradient(to right, var(--dl-color-theme-primary1) 50%, var(--dl-color-theme-neutral-dark) 50%);
        transition: background-position 300ms ease;
        font-weight: 600;
        background-clip: text;
        background-size: 200% 100%;
        background-position: 100%;
        -webkit-text-fill-color: transparent;
    }

    .thq-link:hover {
        background-position: 0 100%;
    }

    .thq-grid-auto-300 {
        display: grid;
        grid-gap: var(--dl-layout-space-oneandhalfunits);
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }

    .thq-animated-group-vertical-reverse {
        gap: var(--dl-layout-space-unit);
        width: 100%;
        display: flex;
        animation: scroll-y 20s linear infinite;
        align-items: flex-start;
        flex-direction: column;
        justify-content: space-around;
        animation-direction: reverse;
    }

    .thq-animated-group-horizontal-reverse {
        gap: var(--dl-layout-space-unit);
        display: flex;
        animation: scroll-x 20s linear infinite;
        min-width: 100%;
        align-items: center;
        flex-shrink: 0;
        justify-content: space-around;
        animation-direction: reverse;
    }

    .thq-animated-group-vertical {
        gap: var(--dl-layout-space-unit);
        width: 100%;
        display: flex;
        animation: scroll-y 20s linear infinite;
        align-items: flex-start;
        flex-direction: column;
        justify-content: space-around;
    }

    .thq-animated-group-horizontal {
        gap: var(--dl-layout-space-unit);
        display: flex;
        animation: scroll-x 20s linear infinite;
        min-width: 100%;
        align-items: center;
        flex-shrink: 0;
        justify-content: space-around;
    }

    .thq-animated-group-container-vertical {
        gap: var(--dl-layout-space-unit);
        display: flex;
        overflow: hidden;
        flex-direction: column;
    }

    .thq-animated-group-container-horizontal {
        gap: var(--dl-layout-space-unit);
        display: flex;
        overflow: hidden;
    }

    .thq-mask-image-vertical {
        mask-image: linear-gradient(to bottom, transparent, black 1%, black 99%, transparent);
    }

    .thq-mask-image-horizontal {
        mask-image: linear-gradient(to right, transparent, black 1%, black 99%, transparent);
    }

    .thq-img-scale {
        transition: 0.3s;
    }

    .thq-img-scale:hover {
        scale: 1.05;
    }

    .thq-animated-card-bg-1 {
        width: 100%;
        height: 100%;
        transition: transform 0.3s;
        border-radius: var(--dl-layout-radius-cardradius);
        background-color: var(--dl-color-theme-accent1);
    }

    .thq-animated-card-bg-2 {
        transition: transform 0.3s;
        border-radius: var(--dl-layout-radius-cardradius);
        background-color: var(--dl-color-theme-accent2);
    }

    .thq-button-animated {
        outline: none;
        z-index: 1;
        overflow: hidden;
        position: relative;
        border-width: 2px;
    }

    .thq-input::placeholder {
        text-align: center;
        vertical-align: middle;
    }

    .thq-animated-group-container-vertical:hover div {
        animation-play-state: paused;
    }

    .thq-animated-group-container-horizontal:hover div {
        animation-play-state: paused;
    }

    .thq-animated-card-bg-2:has([data-animated="true"]:hover) {
        transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(3deg) skew(0deg, 0deg);
    }

    .thq-animated-card-bg-1:has([data-animated="true"]:hover) {
        transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(-6deg) skew(0deg, 0deg);
    }

    .thq-button-animated:before {
        top: 0;
        left: -20%;
        color: var(--dl-color-theme-neutral-light);
        width: 200%;
        height: 101%;
        content: "";
        z-index: 1;
        position: absolute;
        transform: scaleX(0);
        transition: transform 0.5s;
        border-radius: var(--dl-layout-radius-buttonradius);
        background-color: var(--dl-color-theme-neutral-dark);
        transform-origin: 0 0;
        transition-timing-function: cubic-bezier(0.5, 1.6, 0.4, 0.7);
    }

    .thq-button-animated:hover::before {
        color: var(--dl-color-theme-neutral-light);
        z-index: -1;
        transform: scaleX(1);
    }

    .Content {
        font-size: 16px;
        font-family: Inter;
        font-weight: 400;
        line-height: 1.15;
        text-transform: none;
        text-decoration: none;
    }

    @media(max-width: 991px) {
        .thq-grid-4 {
            grid-template-columns: 1fr 1fr 1fr;
        }
    }

    @media(max-width: 767px) {
        .thq-section-padding {
            padding: var(--dl-layout-space-threeunits);
        }
        .thq-flex-column {
            gap: var(--dl-layout-space-oneandhalfunits);
        }
        .thq-flex-row {
            gap: var(--dl-layout-space-oneandhalfunits);
        }
        .thq-grid-6 {
            grid-gap: var(--dl-layout-space-oneandhalfunits);
            grid-template-columns: 1fr 1fr 1fr;
        }
        .thq-grid-5 {
            grid-gap: var(--dl-layout-space-oneandhalfunits);
            grid-template-columns: 1fr 1fr 1fr;
        }
        .thq-card {
            padding: var(--dl-layout-space-oneandhalfunits);
        }
        .thq-grid-3 {
            grid-gap: var(--dl-layout-space-oneandhalfunits);
            grid-template-columns: 1fr 1fr;
        }
        .thq-grid-4 {
            grid-gap: var(--dl-layout-space-oneandhalfunits);
            flex-direction: row;
            grid-template-columns: 1fr 1fr;
        }
        .thq-grid-2 {
            grid-gap: var(--dl-layout-space-oneandhalfunits);
            grid-template-columns: 1fr;
        }
        .thq-img-scale {
            width: 100%;
        }
    }

    @media(max-width: 479px) {
        .thq-section-padding {
            padding: var(--dl-layout-space-oneandhalfunits);
        }
        .thq-flex-column {
            gap: var(--dl-layout-space-unit);
        }
        .thq-flex-row {
            gap: var(--dl-layout-space-unit);
        }
        .thq-grid-6 {
            grid-gap: var(--dl-layout-space-unit);
            grid-template-columns: 1fr 1fr;
        }
        .thq-grid-5 {
            grid-gap: var(--dl-layout-space-unit);
            grid-template-columns: 1fr 1fr;
        }
        .thq-grid-3 {
            grid-gap: var(--dl-layout-space-unit);
            align-items: center;
            grid-template-columns: 1fr;
        }
        .thq-grid-4 {
            grid-gap: var(--dl-layout-space-unit);
            align-items: center;
            flex-direction: column;
            grid-template-columns: 1fr;
        }
        .thq-grid-2 {
            grid-gap: var(--dl-layout-space-unit);
        }
        .thq-grid-auto-300 {
            grid-template-columns: 1fr;
        }
    }

    .welcome-email-default-container1 {
        width: 100%;
        display: flex;
        overflow: auto;
        min-height: 100vh;
        align-items: center;
        flex-direction: column;
    }

    .welcome-email-default-thq-welcome-email-default-elm {
        gap: 36px;
        width: 100%;
        height: auto;
        display: flex;
        padding: 32px 36px;
        overflow: hidden;
        align-items: center;
        flex-shrink: 0;
        flex-direction: column;
        background-color: rgba(250, 248, 255, 1);
    }

    .welcome-email-default-thq-frame2147239077-elm {
        gap: 40px;
        display: flex;
        padding: 40px;
        align-items: flex-start;
        flex-direction: column;
        background-color: rgba(255, 255, 255, 1);
    }

    .welcome-email-default-thq-frame7-elm {
        gap: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .welcome-email-default-thq-frame6-elm {
        gap: 10px;
        display: flex;
        padding: 1px 6px;
        position: relative;
        align-items: center;
        border-radius: 4px;
        flex-direction: column;
        justify-content: center;
        background-color: var(--dl-color-default-primary);
    }

    .welcome-email-default-thq-text-elm10 {
        color: var(--dl-color-default-text);
        height: auto;
        font-size: 16px;
        font-style: Bold;
        text-align: left;
        font-family: Satoshi;
        font-weight: 700;
        line-height: 24px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-text-elm11 {
        color: var(--dl-color-default-text);
        height: auto;
        font-size: 24px;
        font-style: Bold;
        text-align: left;
        font-family: Satoshi;
        font-weight: 700;
        line-height: 42px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-frame2147239078-elm {
        gap: 28px;
        display: flex;
        align-items: flex-start;
        flex-direction: column;
    }

    .welcome-email-default-thq-frame2147239073-elm {
        gap: 20px;
        display: flex;
        align-items: flex-start;
        flex-direction: column;
    }

    .welcome-email-default-thq-text-elm12 {
        color: var(--dl-color-default-sectext);
        width: 280px;
        height: auto;
        font-size: 16px;
        font-style: Regular;
        text-align: left;
        font-family: Satoshi;
        font-weight: 400;
        line-height: 24px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-text-elm13 {
        color: var(--dl-color-default-text);
        width: 459px;
        height: auto;
        font-size: 16px;
        font-style: Regular;
        text-align: left;
        font-family: Satoshi;
        font-weight: 400;
        line-height: 24px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-frame2147239084-elm {
        gap: 40px;
        display: flex;
        padding: 12px 16px;
        align-self: stretch;
        align-items: flex-start;
        border-radius: 16px;
        flex-direction: column;
        background-color: rgba(250, 248, 255, 1);
    }

    .welcome-email-default-thq-frame2147239083-elm {
        display: flex;
        align-self: stretch;
        align-items: flex-start;
        flex-direction: column;
    }

    .welcome-email-default-thq-frame2147239081-elm {
        display: flex;
        align-self: stretch;
        align-items: center;
        flex-shrink: 0;
        border-color: rgba(0, 19, 19, 0.07999999821186066);
        border-style: solid;
        border-width: 0 0 1px;
    }

    .welcome-email-default-thq-frame2147239079-elm1 {
        gap: 10px;
        width: 118px;
        display: flex;
        padding: 16px 24px 16px 16px;
        align-items: center;
        flex-shrink: 0;
        justify-content: center;
    }

    .welcome-email-default-thq-text-elm14 {
        color: var(--dl-color-default-text);
        height: auto;
        font-size: 16px;
        font-style: Medium;
        text-align: left;
        font-family: Satoshi;
        font-weight: 500;
        line-height: 24px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-frame2147239080-elm1 {
        gap: 10px;
        display: flex;
        padding: 16px 12px;
        flex-grow: 1;
        align-items: center;
    }

    .welcome-email-default-thq-text-elm15 {
        color: var(--dl-color-default-accent);
        height: auto;
        font-size: 16px;
        font-style: italic;
        text-align: left;
        font-family: Satoshi;
        font-weight: 400;
        line-height: 24px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-frame2147239082-elm {
        display: flex;
        align-self: stretch;
        align-items: center;
        flex-shrink: 0;
    }

    .welcome-email-default-thq-frame2147239079-elm2 {
        gap: 10px;
        width: 118px;
        display: flex;
        padding: 16px 24px 16px 16px;
        align-items: center;
        flex-shrink: 0;
        justify-content: center;
    }

    .welcome-email-default-thq-text-elm16 {
        color: var(--dl-color-default-text);
        height: auto;
        font-size: 16px;
        font-style: Medium;
        text-align: left;
        font-family: Satoshi;
        font-weight: 500;
        line-height: 24px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-frame2147239080-elm2 {
        gap: 10px;
        display: flex;
        padding: 16px 12px;
        flex-grow: 1;
        align-items: center;
    }

    .welcome-email-default-thq-text-elm17 {
        color: var(--dl-color-default-text);
        height: auto;
        font-size: 16px;
        font-style: italic;
        text-align: left;
        font-family: Satoshi;
        font-weight: 400;
        line-height: 24px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-frame13-elm {
        gap: 24px;
        width: 432px;
        display: flex;
        align-items: flex-start;
        flex-shrink: 0;
        flex-direction: column;
        justify-content: center;
    }

    .welcome-email-default-thq-component30-elm {
        gap: 10px;
        display: flex;
        padding: 12px 48px;
        align-items: center;
        border-radius: 32px;
        justify-content: center;
        background-color: var(--dl-color-default-primary);
    }

    .welcome-email-default-thq-text-elm18 {
        color: var(--dl-color-default-background);
        height: auto;
        font-size: 16px;
        font-style: Medium;
        text-align: center;
        font-family: Satoshi;
        font-weight: 500;
        line-height: 25px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-mingcutearrowleftline-elm {
        width: 24px;
        height: 24px;
        display: flex;
        overflow: hidden;
        position: relative;
        align-items: flex-start;
        flex-shrink: 0;
    }

    .welcome-email-default-thq-group-elm {
        top: 0px;
        left: 0px;
        width: 24px;
        height: 24px;
        display: flex;
        position: absolute;
        align-items: flex-start;
        flex-shrink: 1;
    }

    .welcome-email-default-thq-vector-elm {
        top: 5.355288505554199px;
        left: 3.3432135581970215px;
        width: 18px;
        height: 13px;
        position: absolute;
    }

    .welcome-email-default-thq-footerdesktop-elm {
        gap: 24px;
        width: 600px;
        display: flex;
        padding: 32px 40px 24px;
        align-items: center;
        flex-shrink: 0;
        border-color: rgba(14, 159, 100, 0.10000000149011612);
        border-style: solid;
        border-width: 1px 0 0;
        flex-direction: column;
        background-color: rgba(245, 255, 251, 1);
    }

    .welcome-email-default-thq-frame2147239063-elm {
        gap: 21px;
        display: flex;
        align-self: stretch;
        align-items: center;
        flex-direction: column;
        justify-content: center;
    }

    .welcome-email-default-thq-text-elm19 {
        color: var(--dl-color-default-primary);
        height: auto;
        font-size: 16px;
        font-style: Bold;
        text-align: left;
        font-family: Satoshi;
        font-weight: 700;
        line-height: 20px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-text-elm20 {
        color: var(--dl-color-default-sectext);
        height: auto;
        font-size: 14px;
        align-self: stretch;
        font-style: Regular;
        text-align: center;
        font-family: Satoshi;
        font-weight: 400;
        line-height: 20px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-frame2121454224-elm {
        gap: 7.000000476837158px;
        width: 273px;
        height: 28px;
        display: flex;
        position: relative;
        align-items: flex-start;
        flex-shrink: 0;
    }

    .welcome-email-default-thq-ellipse1069-elm {
        top: 0px;
        left: -11.6669921875px;
        width: 28px;
        height: 28px;
        position: absolute;
    }

    .welcome-email-default-thq-ellipse1070-elm {
        top: 0px;
        left: 28.3330078125px;
        width: 28px;
        height: 28px;
        position: absolute;
    }

    .welcome-email-default-thq-ellipse1071-elm {
        top: 0px;
        left: 68.3330078125px;
        width: 28px;
        height: 28px;
        position: absolute;
    }

    .welcome-email-default-thq-ellipse1074-elm {
        top: 0px;
        left: 108.3330078125px;
        width: 28px;
        height: 28px;
        position: absolute;
    }

    .welcome-email-default-thq-ellipse1073-elm {
        top: 0px;
        left: 148.3330078125px;
        width: 28px;
        height: 28px;
        position: absolute;
    }

    .welcome-email-default-thq-ellipse1072-elm {
        top: 0px;
        left: 188.3330078125px;
        width: 28px;
        height: 28px;
        position: absolute;
    }

    .welcome-email-default-thq-ellipse1067-elm {
        top: 0px;
        left: 228.3330078125px;
        width: 28px;
        height: 28px;
        position: absolute;
    }

    .welcome-email-default-thq-ellipse1075-elm {
        top: 0px;
        left: 268.3330078125px;
        width: 28px;
        height: 28px;
        position: absolute;
    }

    .welcome-email-default-thq-text-elm21 {
        color: rgba(49, 49, 49, 1);
        height: auto;
        font-size: 14px;
        align-self: stretch;
        font-style: Regular;
        text-align: center;
        font-family: Satoshi;
        font-weight: 400;
        line-height: 20px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-text-elm22 {
        color: var(--dl-color-default-sectext);
        font-weight: 400;
    }

    .welcome-email-default-thq-frame2147239069-elm {
        gap: 12px;
        display: flex;
        align-items: center;
        flex-direction: column;
    }

    .welcome-email-default-thq-frame2147239067-elm {
        gap: 10px;
        height: 16px;
        display: flex;
        align-items: center;
        flex-shrink: 0;
        border-color: rgba(49, 49, 49, 1);
        border-style: solid;
        border-width: 0 0 0.800000011920929px;
        justify-content: center;
    }

    .welcome-email-default-thq-text-elm24 {
        color: var(--dl-color-default-sectext);
        height: auto;
        font-size: 12px;
        font-style: Regular;
        text-align: center;
        font-family: Satoshi;
        font-weight: 400;
        line-height: 20px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-frame2147239076-elm {
        gap: 6px;
        display: flex;
        align-items: center;
    }

    .welcome-email-default-thq-logoswhatsappicon-elm {
        width: 20px;
        height: 20px;
    }

    .welcome-email-default-thq-text-elm25 {
        color: var(--dl-color-default-sectext);
        height: auto;
        font-size: 12px;
        font-style: Medium;
        text-align: left;
        font-family: Satoshi;
        font-weight: 500;
        line-height: 24px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-text-elm26 {
        color: rgba(49, 49, 49, 1);
        height: auto;
        font-size: 12px;
        align-self: stretch;
        font-style: Regular;
        text-align: center;
        font-family: Satoshi;
        font-weight: 400;
        line-height: 20px;
        font-stretch: normal;
        text-decoration: none;
    }

    .welcome-email-default-thq-text-elm27 {
        color: var(--dl-color-default-sectext);
        font-weight: 400;
    }
    .welcome-email-default-thq-text-elm28 {
        color: var(--dl-color-default-primary);
        font-weight: 700;
    }
    .welcome-email-default-link {
        display: contents;
    }
    .welcome-email-default-container2 {
        right: 50px;
        border: 1px solid #ffffff5c;
        bottom: 30px;
        display: flex;
        z-index: 22;
        position: fixed;
        box-shadow: 5px 5px 10px 0px rgba(31, 31, 31, 0.4);
        min-height: auto;
        align-items: center;
        padding-top: 8px;
        padding-left: 12px;
        border-radius: 8px;
        padding-right: 12px;
        padding-bottom: 8px;
        backdrop-filter: blur(6px);
        background-color: rgba(41, 41, 41, 0.41);
    }
    .welcome-email-default-icon1 {
        width: 24px;
        margin-right: 4px;
    }
    .welcome-email-default-text {
        color: white;
        font-size: 13px;
        font-style: normal;
        font-weight: 500;
        line-height: 24px;
    }
</style>
<title>{{ $data['title'] ?? $siteName }}</title>
</head>
<body>
    <div class="welcome-email-default-container1">
        <div class="welcome-email-default-thq-welcome-email-default-elm">
            <div class="welcome-email-default-thq-frame2147239077-elm">
                @if ($logoUrl)
                    <div class="welcome-email-default-thq-frame7-elm">
                        <div class="welcome-email-default-thq-frame6-elm"></div>
                        <span class="welcome-email-default-thq-text-elm10">
                            <img src="{{ $logoUrl }}" style="height: 70px; width: 70px;" alt="company-logo" />
                        </span>
                    </div>
                @endif

                <div class="welcome-email-default-thq-frame2147239082-elm">
                    <div class="welcome-email-default-thq-frame2147239079-elm2">
                        <span class="welcome-email-default-thq-text-elm16">
                          Password:
                        </span>
                    </div>
                    <div class="welcome-email-default-thq-frame2147239080-elm2">
                        <span class="welcome-email-default-thq-text-elm17">
                          {{ $password }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="welcome-email-default-thq-footerdesktop-elm">
                <div class="welcome-email-default-thq-frame2147239063-elm">
                    @if ($logoUrl)
                        <span class="welcome-email-default-thq-text-elm19">
                            <img src="{{ $logoUrl }}" style="height: 70px; width: 70px;" alt="company-logo" />
                        </span>
                    @endif

                    <span class="welcome-email-default-thq-text-elm20">
                        Never miss a beat on our latest product updates and events
                    </span>
                </div>
                <div class="welcome-email-default-thq-frame2121454224-elm">
                    <img
                        src="{{ asset('images/ellipse-1.png') }}"
                        alt="Ellipse1069I911"
                        class="welcome-email-default-thq-ellipse1069-elm"
                    />
                    <img
                        src="{{ asset('images/ellipse-2.png') }}"
                        alt="Ellipse1070I911"
                        class="welcome-email-default-thq-ellipse1070-elm"
                    />
                    <img
                        src="{{ asset('images/ellipse-3.png') }}"
                        alt="Ellipse1071I911"
                        class="welcome-email-default-thq-ellipse1071-elm"
                    />
                    <img
                        src="{{ asset('images/ellipse-4.png') }}"
                        alt="Ellipse1074I911"
                        class="welcome-email-default-thq-ellipse1074-elm"
                    />
                    <img
                        src="{{ asset('images/ellipse-5.png') }}"
                        alt="Ellipse1073I911"
                        class="welcome-email-default-thq-ellipse1073-elm"
                    />
                    <img
                        src="{{ asset('images/ellipse-6.png') }}"
                        alt="Ellipse1072I911"
                        class="welcome-email-default-thq-ellipse1072-elm"
                    />
                    <img
                        src="{{ asset('images/ellipse-7.png') }}"
                        alt="Ellipse1067I911"
                        class="welcome-email-default-thq-ellipse1067-elm"
                    />
                    <img
                        src="{{ asset('images/ellipse-8.png') }}"
                        alt="Ellipse1075I911"
                        class="welcome-email-default-thq-ellipse1075-elm"
                    />
                </div>
                <span class="welcome-email-default-thq-text-elm21">
                    <span class="welcome-email-default-thq-text-elm22">
                        Trade on the go with
                    </span>
                    <span>Brand name</span>
                </span>
                <div class="welcome-email-default-thq-frame2147239069-elm">
                    <div class="welcome-email-default-thq-frame2147239067-elm">
                        <span class="welcome-email-default-thq-text-elm24">
                            Latest promotions
                        </span>
                    </div>
                    <div class="welcome-email-default-thq-frame2147239076-elm">
                        <img
                            src="{{ asset('images/whatsapp-icon.png') }}"
                            alt="logoswhatsappiconI911"
                            class="welcome-email-default-thq-logoswhatsappicon-elm"
                        />
                        <span class="welcome-email-default-thq-text-elm25">
                            Support
                        </span>
                    </div>
                </div>
                <span class="welcome-email-default-thq-text-elm26">
                    <span class="welcome-email-default-thq-text-elm27">
                      Copyright @2018 - {{ date('Y') }}
                    </span>
                    <span class="welcome-email-default-thq-text-elm28">
                      {{ $siteName }}.
                    </span>
                    <span>All rights reserved</span>
                </span>
            </div>
        </div>
    </div>
</body>
</html>
