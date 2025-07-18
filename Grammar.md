# Grammar of Lox

```text
program         → declaration* EOF ;
declaration     → funDecl
                | varDecl
                | statement ;
funDecl         → "fun" function ;
function        → IDENTIFIER "(" parameters? ")" block;
varDecl         → "var" IDENTIFIER ( "=" expression )? ";" ;
statement       → exprStmt
                | forStmt
                | ifStmt
                | printStmt
                | returnStmt
                | whileStmt
                | block;
forStmt         → "for" "(" ( varDecl | exprStmt | ";" ) expression? ";" expression? ")" statement ;
ifStmt          → "if" "(" expression ")" statement
                ( "else" statement )? ;
block           → "{" declaration * "}" ;
exprStmt        → expression ";" ;
printStmt       → "print" expression ";" ;
returnStmt      → "return" expression? ";" ;
whileStmt       → "while" "(" expression ")" statement ;
expression      → assignment ;
assignment      → IDENTIFIER "=" assignment
                | logic_or;
logic_or        → logic_and ( "or" logic_and )* ;
logic_and       → equality ( "and" equality )* ;
equality        → comparison ( ( "!=" | "==" ) comparison )* ;
comparison      → term ( ( ">" | ">=" | "<" | "<=" ) term )* ;
term            → factor ( ( "-" | "+" ) factor )* ;
factor          → unary ( ( "/" | "*" ) unary )* ;
unary           → ( "!" | "-" ) unary
                | call ;
call            → primary ( "(" arguments? ")" )* ;
arguments       → expression ( "," expression )* ;
parameters      → IDENTIFIER ( "," IDENTIFIER )* ;
primary         → NUMBER | STRING | "true" | "false" | "nil"
                | "(" expression ")"
                | IDENTIFIER ;
