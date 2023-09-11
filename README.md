# Web Identifier
(A working name until I can think of something more unique)

## What This Does

A while ago I worked within a group which had a rather prominent concern of information leaks.
We discussed methods we could use to identify the sources of leaks, this was the result.

This script provides two methods for placing an encrypted identifier (such as a user ID) into your webpages via two mediums

### Text mode

The text mode inserts zero-width spaces between each space in your content.
The zero-width spaces are encoded in binary and encrypted to obfuscate the original identifier.

Provided it is not altered, this mode allows you to identify text that has been directly copied from your source.

### Image mode

Like a QR Code, image mode generates a pattern-like image to be used as a background on your pages.

This mode is useful for identifying leaks via screenshots of your website.

## Requirements

- md5sum