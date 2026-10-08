<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

final class XPathTokenizer
{
    public function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $i = 0;

        // echo "-> " . $expression . "<br>";

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            // whitespace überspringen
            if ($char === ' ' || $char === "\t" || $char === "\n" || $char === "\r") {
                continue;
            }

            // Predicate erkennen
            if ($char === '[') {
                $tokens[] = ['type' => 'PRED_OPEN', 'value' => '['];
                continue;
            }

            if ($char === ']') {
                $tokens[] = ['type' => 'PRED_CLOSE', 'value' => ']'];
                continue;
            }

            // Axis Seperator
            if ($char === ':' && $i + 1 < $length && $expression[$i + 1] === ':') {
                $tokens[] = ['type' => 'AXIS_SEP', 'value' => '::'];
                $i++;
                continue;
            }

            // Parent axis: ..
            if ($char === '.' && $i + 1 < $length && $expression[$i + 1] === '.') {
                $tokens[] = [
                    'type'  => 'AXIS',
                    'value' => '..',
                ];
                $i++;
                continue;
            }

            // Self axis: .
            if ($char === '.') {
                $tokens[] = [
                    'type' => 'AXIS',
                    'value' => '.',
                ];
                continue;
            }

            // Path operator: /
            if ($char === '/') {
                // check for //
                if ($i + 1 < $length && $expression[$i + 1] === '/') {
                    $tokens[] = ['type' => 'PATH', 'value' => '//'];
                    $i++;
                    continue;
                }

                $tokens[] = ['type' => 'PATH', 'value' => '/'];
                continue;
            }

            // Attribut @
            if ($char === '@') {
                $tokens[] = ['type' => 'ATTR', 'value' => '@'];
                continue;
            }

            // Variable $name
            if ($char === '$') {
                $start = ++$i;
                while ($i < $length && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) {
                    $i++;
                }
                $name = substr($expression, $start, $i - $start);
                $i--;
                $tokens[] = ['type' => 'VARIABLE', 'value' => $name];
                continue;
            }

            // Klammern und Komma
            if ($char === '(' || $char === ')' || $char === ',') {
                $tokens[] = ['type' => 'CHAR', 'value' => $char];
                continue;
            }

            // String-Literal '...' or "..."
            if ($char === '\'' || $char === '"') {
                $quote = $char;
                $start = ++$i;
                while ($i < $length && $expression[$i] !== $quote) {
                    $i++;
                }
                $tokens[] = ['type' => 'STRING', 'value' => substr($expression, $start, $i - $start)];
                continue;
            }

            // Operatoren: =, !=, <, >, <=, >=, *
            if ($char === '=' || $char === '!' || $char === '<' || $char === '>' || $char === '*') {
                $operator = $char;
                if ($i + 1 < $length && $expression[$i + 1] === '=') {
                    $operator .= '=';
                    $i++;
                }
                $tokens[] = ['type' => 'OP', 'value' => $operator];
                continue;
            }

            // Additive Operatoren: +, -
            if ($char === '+' || $char === '-') {
                $tokens[] = ['type' => 'OP', 'value' => $char];
                continue;
            }

            // Namen / Keywords (and, or, div, mod, Funktionen, Pfade)
            if (ctype_alpha($char) || $char === '_' || $char === ':') {
                $start = $i;
                while (
                    $i < $length &&
                    (ctype_alnum($expression[$i]) || $expression[$i] === '_' || $expression[$i] === ':' || $expression[$i] === '-')
                ) {
                    $i++;
                }
                
                $value = substr($expression, $start, $i - $start);

                // and/or/div/mod als OP
                if (in_array($value, ['and', 'or', 'div', 'mod'], true)) {
                    $tokens[] = ['type' => 'OP', 'value' => $value];
                } else {
                    $tokens[] = ['type' => 'NAME', 'value' => $value];
                }

                $i--;

                continue;
            }

            // Number Werte (Floats, ganze Zahlen)
            if (ctype_digit($char)) {
                $number = $char;
                $i++;

                // consume integer part
                while ($i < $length && ctype_digit($expression[$i])) {
                    $number .= $expression[$i];
                    $i++;
                }

                // decimal part
                if ($i < $length && $expression[$i] === '.') {
                    // lookahead: next char must be a digit
                    if ($i + 1 < $length && ctype_digit($expression[$i + 1])) {
                        $number .= '.';
                        $i++; // move past the dot

                        // consume fractional digits
                        while ($i < $length && ctype_digit($expression[$i])) {
                            $number .= $expression[$i];
                            $i++;
                        }
                    }
                }

                $tokens[] = ['type' => 'NUMBER', 'value' => $number];
                
                // Index Korrektur wegen Lookahead-Schleife, die den Iterator ein Zeichen weiter gesetzt hat
                $i--;
                continue;
            }

            // Fallback: einzelnes Zeichen als CHAR
            $tokens[] = ['type' => 'CHAR', 'value' => $char];
        }

        // if (str_contains($expression, '/xs:decimal(')) {
        //     echo " -> " . $expression . "<br>";
        //     foreach ($tokens as $token) {
        //         echo $token['type'] . " => " . $token['value'] . "<br>";
        //     }
        //     die();
        // }

        // if ($expression === "string-length(substring-after(ram:LineTotalAmount,'.'))<=2") {
        //     foreach ($tokens as $token) {
        //         echo $token['type'] . " => " . $token['value'] . "<br>";
        //     }
        //     die();
        // }

        return $tokens;
    }
}
