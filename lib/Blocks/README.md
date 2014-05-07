# Blocks
Classes that model the Object graph of our test suite. In the most abstract sense
every Blocks is a node in the tree-graph representing our test suite.

TestMethods, created via `it`, must be leaf nodes.

The most common concrete subclasses are Describe and TestMethod.
